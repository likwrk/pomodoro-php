<?php

class TimersService
{

	private $db;

	public function __construct(\Doctrine\DBAL\Connection $db)
	{
		$this->db = $db;
	}

	public function getActive()
	{
		return array_map([$this, 'formatTimer'], $this->db->fetchAll('
			SELECT 
				timers.id, timers.type, types.name, timers.start, timers.end, (timers.start + types.length - ?) as timeleft, timers.completed
			FROM 
				timers 
			LEFT JOIN 
				types ON timers.type = types.id
			WHERE 
				end is null 
			ORDER BY 
				start DESC
		', [time()]));
	}

	public function getCompletedByDay() {
		$timers = $this->getCompleted();
		$timersByDay = [];
		foreach ($timers as $timer) {
			if ($timer['type'] !== 1) continue;
			$day = date("Y-m-d l", $timer['start']);
			if (!isset($timersByDay[$day])) $timersByDay[$day] = ['day' => $day, 'timers' => []];
			$timersByDay[$day]['timers'][] = $timer;
		}
		return $timersByDay;
	}

	public function getCompleted()
	{
		return array_map([$this, 'formatTimer'], $this->db->fetchAll('
			SELECT 
				timers.id, timers.type, types.name, timers.start, timers.end, timers.completed
			FROM 
				timers 
			LEFT JOIN 
				types ON timers.type = types.id
			WHERE 
				timers.completed = 1
			ORDER BY 
				start DESC
		', [time()]));
	}

	public function getAll()
	{
		return array_map([$this, 'formatTimer'], $this->db->fetchAll('
			SELECT 
				timers.id, timers.type, types.name, timers.start, timers.end, timers.completed
			FROM 
				timers 
			LEFT JOIN 
				types ON timers.type = types.id
			ORDER BY 
				start ASC
		', [time()]));
	}

	public function stop($id, $completed = true)
	{
		return $this->db->executeUpdate('UPDATE timers SET end = ? , completed = ? WHERE id = ? LIMIT 1', [time(), (int)$completed, (int)$id]);
	}

	public function start($type)
	{
		$this->db->insert('timers', ['start' => time(), 'type' => (int) $type]);
		return $this->db->lastInsertId();
	}

	public function getNextType()
	{
		$lastTimer = $this->formatTimer($this->db->fetchAssoc('SELECT * FROM timers ORDER BY start DESC LIMIT 1'));
		// if last time was a break, then next is pomodoro
		if ($lastTimer['type'] > 1) {
			return 1;
		}
		if ($lastTimer['type'] === 1 && $lastTimer['completed'] === 0) {
			return 1;
		}
		$lastLong = $this->db->fetchAssoc('SELECT * FROM timers WHERE type = 3 ORDER BY start DESC LIMIT 1');
		$prevPomodoros = $this->db->fetchColumn('SELECT count(*) FROM timers WHERE completed = 1 AND type = 1 AND start > ?', [$lastLong['start']]);
		if ($prevPomodoros < 4) {
			return 2;
		}
		return 3;
	}

	public function stopAll()
	{
		$active = $this->getActive();
		foreach ($active as $timer) {
			$this->stop($timer['id'], $timer['timeleft'] < 1);
		}
	}

	public function stopCompleted()
	{
		$active = $this->getActive();
		foreach ($active as $timer) {
			if ($timer['timeleft'] < 1) {
				$this->stop($timer['id']);
			}
		}
	}

	private function formatTimer($timer) {
		return [
			'id' => !empty($timer['id']) ? (int)$timer['id'] : null,
			'type' => !empty($timer['type']) ? (int)$timer['type'] : null,
			'name' => !empty($timer['name']) ? $timer['name'] : null,
			'start' => !empty($timer['start']) ? (int)$timer['start'] : null,
			'end' => !empty($timer['end']) ? (int)$timer['end'] : null,
			'timeleft' => !empty($timer['timeleft']) ? (int)$timer['timeleft'] : null,
			'completed' => (int)$timer['completed']
		];
	}
}