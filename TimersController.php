<?php

use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TimersController implements ControllerProviderInterface
{

	private $timersService;

	public function __construct(TimersService $timersService)
	{
		$this->timersService = $timersService;
	}

	public function connect(Application $app)
	{
		$controllers = $app['controllers_factory'];
		$controllers->get('/timers', function() {
			return $this->getActive();
		});
		$controllers->get('/timers/all', function() {
			return $this->getAll();
		});
		$controllers->get('/timers/completed', function() {
			return $this->getCompleted();
		});
		$controllers->post('/timers/start', function() {
			return $this->startAuto();
		});
		$controllers->post('/timers/start/pomodoro', function() {
			return $this->startPomodoro();
		});
		$controllers->post('/timers/start/short', function() {
			return $this->startShort();
		});
		$controllers->post('/timers/start/long', function() {
			return $this->startLong();
		});
		$controllers->put('/timers/{id}', function(Request $request, $id) {
			$data = $request->request->all();
			return $this->updateTimer($id, $data);
		});
		return $controllers;
	}

	/**
	 * Use for getting current timer info
	 * Should be called from some loop
	 * If timeleft is 0 or less, we complete this timer.
	 * After that alert should be called
	 * @return JsonResponse
	 */
	public function getActive() {
		$active = $this->timersService->getActive();
		$this->timersService->stopCompleted();
		$length = count($active);
		if ($length === 0) {
			return new JsonResponse([
				'start' => null,
				'type' => null,
				'timeleft' => null
			]);
		}
		return new JsonResponse($active[$length - 1]);
	}

	/**
	 * Retrieves all timers from database.
	 * Can be slow.
	 * @return JsonResponse
	 */
	public function getAll() {
		$active = $this->timersService->getAll();
		return new JsonResponse($active);
	}

	/**
	 * Retrieves only completed timers from database.
	 * @return JsonResponse
	 */
	public function getCompleted() {
		$active = $this->timersService->getCompleted();
		return new JsonResponse(array_map([$this, 'formatTimer'], $active));
	}

	public function startAuto()
	{
		return $this->start($this->timersService->getNextType());
	}

	private function start($type)
	{
		$this->timersService->stopAll();
		$this->timersService->start($type);
		return new JsonResponse(['status' => 'success']);
	}

	public function startPomodoro()
	{
		return $this->start(1);
	}

	public function startShort()
	{
		return $this->start(2);
	}

	public function startLong()
	{
		return $this->start(3);
	}

	private function updateTimer($id, $data)
	{
		if (isset($data['comment'])) {
			$this->timersService->updateComment($id, $data['comment']);
		}
		if (isset($data['logged'])) {
			$this->timersService->updateLogged($id, $data['logged']);
		}
		return new JsonResponse($this->timersService->getById($id));
	}

}