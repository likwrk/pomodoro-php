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
				timers.*, (timers.start + types.length - ?) as timeleft, types.name
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

	public function updateComment($id, $comment = '') {
		return $this->db->executeUpdate('UPDATE timers SET comment = ? WHERE id = ? LIMIT 1', [$comment, (int)$id]);
	}

	public function updateLogged($id, $logged = true)
	{
		return $this->db->executeUpdate('UPDATE timers SET logged = ? WHERE id = ? LIMIT 1', [(int)$logged, (int)$id]);
	}

	public function getCompleted()
	{
		return array_map([$this, 'formatTimer'], $this->db->fetchAll('
			SELECT 
				timers.*
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
				timers.*
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

	public function getById($id)
	{
		return $this->formatTimer($this->db->fetchAssoc('SELECT * FROM timers WHERE id = ? LIMIT 1', [(int)$id]));
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
		$lastLongStart = $this->db->fetchColumn('SELECT start FROM timers WHERE type = 3 ORDER BY start DESC LIMIT 1');
		if (empty($lastLongStart)) {
			$lastLongStart = 0;
		}
		$prevPomodoros = $this->db->fetchColumn('SELECT count(*) FROM timers WHERE completed = 1 AND type = 1 AND start > ?', [(int)$lastLongStart]);
		if ($prevPomodoros < 4) {
			return 2; // short break
		}
		return 3; // long break
	}

	public function stopAll()
	{
		$active = $this->getActive();
		foreach ($active as $timer) {
			// always mark breaks as completed
			if ($timer['type'] > 1) {
				$this->stop($timer['id'], true);
			} else {
				$this->stop($timer['id'], $timer['timeleft'] < 1);
			}
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
			'completed' => (int)$timer['completed'],
			'logged' => (int)$timer['logged'],
			'comment' => !empty($timer['comment']) ? $timer['comment'] : ''
		];
	}
}