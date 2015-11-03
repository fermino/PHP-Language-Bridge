<?php
	require_once __DIR__ . '/LanguageManager.php';

	require_once __DIR__ . '/LanguageBridgeException.php';

	require_once __DIR__ . '/ProtocolNode.php';

	require_once __DIR__ . '/BindedVariable.php';

	class LanguageBridge extends LanguageManager
	{
		private $Language = null;
		private $Filename = null;

		private $WorkingDirectory = null;

		private $Process = null;

		private $Variables = array();

		public function __construct($Language, $Filename, $WorkingDirectory = null)
		{
			$this->Language = $Language;
			$this->Filename = $Filename;

			$this->WorkingDirectory = $WorkingDirectory;

			parent::__construct();
		}

		public function BindVariable($Name, $Value)
		{
			$this->Variables[] = new BindedVariable($Name, $Value);
		}

		public function Execute()
		{
			$this->Process = parent::Execute($this->Language);

			if($this->Process->IsRunning())
			{
				if(fwrite($this->Process->GetStdIn(), (new ProtocolNode(0x00, array('Filename' => realpath($this->Filename))))->GetProtocolNode()))
				{
					if($this->Process->IsRunning())
					{
						# Bridge

						foreach($this->Variables as $Variable) // Variables
							fwrite($this->Process->GetStdIn(), $Variable->GetProtocolNode());

						if($this->Process->IsRunning())
						{
							# Execute

							fwrite($this->Process->GetStdIn(), (new ProtocolNode(0xff))->GetProtocolNode());

							return $this;
						}
					}
				}
			}
		}

		public function ExecuteTask()
		{
			$Task = fgets($this->Process->GetStdOut());

			if(!empty($Task))
			{
				echo $Task;
			}

			// Sleep
		}

		public function IsRunning()
		{ return $this->Process->IsRunning(); }

		public function Close()
		{ return $this->Process->Close(); }

		public function Terminate($Signal = 15)
		{ return $this->Process->Terminate($Signal); }
	}