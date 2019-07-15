<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PirepCancelled;
use App\Http\Requests\Acars\EventRequest;
use App\Http\Requests\Acars\LogRequest;
use App\Http\Requests\Acars\PositionRequest;
use App\Http\Resources\AcarsRoute as AcarsRouteResource;
use App\Contracts\Controller;
use App\Models\Acars;
use App\Models\Enums\AcarsType;
use App\Models\Enums\PirepStatus;
use App\Models\Pirep;
use App\Repositories\AcarsRepository;
use App\Repositories\PirepRepository;
use App\Services\GeoService;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Log;

/**
 * Class AcarsController
 */
class AcarsController extends Controller
{
    private $acarsRepo;
    private $geoSvc;
    private $pirepRepo;

    /**
     * AcarsController constructor.
     *
     * @param AcarsRepository $acarsRepo
     * @param GeoService      $geoSvc
     * @param PirepRepository $pirepRepo
     */
    public function __construct(
        AcarsRepository $acarsRepo,
        GeoService $geoSvc,
        PirepRepository $pirepRepo
    ) {
        $this->geoSvc = $geoSvc;
        $this->acarsRepo = $acarsRepo;
        $this->pirepRepo = $pirepRepo;
    }

    /**
     * Check if a PIREP is cancelled
     *
     * @param $pirep
     *
     * @throws \App\Exceptions\PirepCancelled
     */
    protected function checkCancelled(Pirep $pirep)
    {
        if ($pirep->cancelled) {
            throw new PirepCancelled();
        }
    }

    /**
     * Return all of the flights (as points) in GeoJSON format
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        $pireps = $this->acarsRepo->getPositions(setting('acars.live_time'));
        $positions = $this->geoSvc->getFeatureForLiveFlights($pireps);

        return response(json_encode($positions), 200, [
            'Content-type' => 'application/json',
        ]);
    }

    /**
     * Return the GeoJSON for the ACARS line
     *
     * @param         $pirep_id
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory
     */
    public function acars_geojson($pirep_id, Request $request)
    {
        $pirep = Pirep::find($pirep_id);
        $geodata = $this->geoSvc->getFeatureFromAcars($pirep);

        return response(\json_encode($geodata), 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Return the routes for the ACARS line
     *
     * @param         $id
     * @param Request $request
     *
     * @return AcarsRouteResource
     */
    public function acars_get($id, Request $request)
    {
        $this->pirepRepo->find($id);

        return new AcarsRouteResource(Acars::where([
            'pirep_id' => $id,
            'type'     => AcarsType::FLIGHT_PATH,
        ])->orderBy('sim_time', 'asc')->get());
    }

    /**
     * Post ACARS updates for a PIREP
     *
     * @param                 $id
     * @param PositionRequest $request
     *
     * @throws \App\Exceptions\PirepCancelled
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function acars_store($id, PositionRequest $request)
    {
        // Check if the status is cancelled...
        $pirep = Pirep::find($id);
        $this->checkCancelled($pirep);

        Log::debug(
            'Posting ACARS update (user: '.Auth::user()->pilot_id.', pirep id :'.$id.'): ',
            $request->post()
        );

        $count = 0;
        $positions = $request->post('positions');
        foreach ($positions as $position) {
            $position['pirep_id'] = $id;
            $position['type'] = AcarsType::FLIGHT_PATH;

            if (array_key_exists('sim_time', $position)) {
                if ($position['sim_time'] instanceof \DateTime) {
                    $position['sim_time'] = Carbon::instance($position['sim_time']);
                } else {
                    $position['sim_time'] = Carbon::createFromTimeString($position['sim_time']);
                }
            }

            if (array_key_exists('created_at', $position)) {
                if ($position['created_at'] instanceof \DateTime) {
                    $position['created_at'] = Carbon::instance($position['created_at']);
                } else {
                    $position['created_at'] = Carbon::createFromTimeString($position['created_at']);
                }
            }

            $update = Acars::create($position);
            $update->save();

            $count++;
        }

        // Change the PIREP status if it's as SCHEDULED before
        if ($pirep->status === PirepStatus::INITIATED) {
            $pirep->status = PirepStatus::AIRBORNE;
        }

        $pirep->save();

        return $this->message($count.' positions added', $count);
    }

    /**
     * Post ACARS LOG update for a PIREP. These updates won't show up on the map
     * But rather in a log file.
     *
     * @param            $id
     * @param LogRequest $request
     *
     * @throws \App\Exceptions\PirepCancelled
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function acars_logs($id, LogRequest $request)
    {
        // Check if the status is cancelled...
        $pirep = Pirep::find($id);
        $this->checkCancelled($pirep);

        Log::debug('Posting ACARS log, PIREP: '.$id, $request->post());

        $count = 0;
        $logs = $request->post('logs');
        foreach ($logs as $log) {
            $log['pirep_id'] = $id;
            $log['type'] = AcarsType::LOG;

            if (array_key_exists('sim_time', $log)) {
                $log['sim_time'] = Carbon::createFromTimeString($log['sim_time']);
            }

            if (array_key_exists('created_at', $log)) {
                $log['created_at'] = Carbon::createFromTimeString($log['created_at']);
            }

            $acars = Acars::create($log);
            $acars->save();
            $count++;
        }

        return $this->message($count.' logs added', $count);
    }

    /**
     * Post ACARS LOG update for a PIREP. These updates won't show up on the map
     * But rather in a log file.
     *
     * @param              $id
     * @param EventRequest $request
     *
     * @throws \App\Exceptions\PirepCancelled
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function acars_events($id, EventRequest $request)
    {
        // Check if the status is cancelled...
        $pirep = Pirep::find($id);
        $this->checkCancelled($pirep);

        Log::debug('Posting ACARS event, PIREP: '.$id, $request->post());

        $count = 0;
        $logs = $request->post('events');
        foreach ($logs as $log) {
            $log['pirep_id'] = $id;
            $log['type'] = AcarsType::LOG;
            $log['log'] = $log['event'];

            if (array_key_exists('sim_time', $log)) {
                $log['sim_time'] = Carbon::createFromTimeString($log['sim_time']);
            }

            if (array_key_exists('created_at', $log)) {
                $log['created_at'] = Carbon::createFromTimeString($log['created_at']);
            }

            $acars = Acars::create($log);
            $acars->save();
            $count++;
        }

        return $this->message($count.' logs added', $count);
    }
}
