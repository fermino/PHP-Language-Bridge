<?php
	class Process
	{
		const STDIN = 0;
		const STDOUT = 1;
		const STDERR = 2;

		private $ExecutableFilename = null;

		private $Process = null;
		private $Std = array();

		private $Blocking = true;
		private $Escape = true;

		public function __construct($ExecutableFilename, $Blocking = true, $Escape = true)
		{
			$this->ExecutableFilename = $ExecutableFilename;

			$this->Blocking = (int) $Blocking;
			$this->Escape = (bool) $Escape;
		}

		private function Reset()
		{
			$this->Process = null;
			$this->Std = array();
		}

		public function Execute()
		{
			$Arguments = func_get_args();

			$Command = $this->ExecutableFilename;

			foreach($Arguments as $Argument)
				if(!empty($Argument))
					$Command .= ' ' . ($this->Escape ? escapeshellarg($Argument) : $Argument);

			# Execute

			$this->Process = proc_open($Command, array(self::STDIN => array('pipe', 'r'), self::STDOUT => array('pipe', 'w'), self::STDERR => array('pipe', 'w')), $this->Std);

			if(is_resource($this->Process))
			{
				$this->SetBlocking($this->Blocking);

				return $this;
			}

			$this->Reset();

			return false;
		}

		public function SetBlocking($Blocking)
		{
			$this->Blocking = (int) $Blocking;

			if(!empty($this->Std))
			{
				stream_set_blocking($this->Std[self::STDIN], $this->Blocking);
				stream_set_blocking($this->Std[self::STDOUT], $this->Blocking);
				stream_set_blocking($this->Std[self::STDERR], $this->Blocking);
			}

			return true;
		}

		public function IsRunning()
		{
			if(is_resource($this->Process))
			{
				$Status = proc_get_status($this->Process);

				if(isset($Status['running']))
					return $Status['running'];
			}

			return false;
		}

		public function Close()
		{
			if(is_resource($this->Process))
			{
				fclose($this->Std[self::STDIN]);
				fclose($this->Std[self::STDOUT]);
				fclose($this->Std[self::STDERR]);

				$ReturnCode = proc_close($this->Process);

				$this->Reset();

				return $ReturnCode !== -1 ? $ReturnCode : false;
			}

			return true;
		}

		public function Terminate($Signal = 15)
		{
			if(is_resource($this->Process))
			{
				$ReturnCode = proc_terminate($this->Process, $Signal);

				$this->Reset();

				return $ReturnCode;
			}

			return true;
		}

		public function GetStdIn()
		{ return isset($this->Std[self::STDIN]) ? $this->Std[self::STDIN] : false; }

		public function GetStdOut()
		{ return isset($this->Std[self::STDOUT]) ? $this->Std[self::STDOUT] : false; }

		public function GetStdErr()
		{ return isset($this->Std[self::STDERR]) ? $this->Std[self::STDERR] : false; }
	}