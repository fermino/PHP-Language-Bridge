<?php
	require_once __DIR__ . '/LanguageManager.php';

	require_once __DIR__ . '/LanguageBridgeException.php';

	require_once __DIR__ . '/ProtocolNode.php';

	require_once __DIR__ . '/BindedVariable.php';
	require_once __DIR__ . '/BindedFunction.php';

	class LanguageBridge
	{
		private $Language = null;
		private $Filename = null;

		private $WorkingDirectory = null;

		private $Process = null;

		private $Variables = array();
		private $Functions = array();

		public function __construct($Language, $Filename, $WorkingDirectory = null)
		{
			$this->Language = $Language;
			$this->Filename = $Filename;
		}

		public function BindVariable($Name, $Value)
		{
			$this->Variables[] = new BindedVariable($Name, $Value);
		}

		public function BindFunction($Name, callable $Function)
		{
			$this->Functions[$Name] = new BindedFunction($Name, $Function);
		}

		public function Execute()
		{
			$this->Process = LanguageManager::Execute($this->Language);

			if($this->Process->IsRunning())
			{
				if(is_file($this->Filename) && is_readable($this->Filename))
				{
					if(fwrite($this->Process->GetStdIn(), (new ProtocolNode(0x00, array('Filename' => $this->Filename)))->GetProtocolNode()))
					{
						if($this->Process->IsRunning())
						{
							# Bridge

							foreach($this->Variables as $Variable) // Variables
								fwrite($this->Process->GetStdIn(), $Variable->GetProtocolNode());

							foreach($this->Functions as $Function)
								fwrite($this->Process->GetStdIn(), $Function->GetProtocolNode());

							if($this->Process->IsRunning())
							{
								# Execute

								fwrite($this->Process->GetStdIn(), (new ProtocolNode(0xff))->GetProtocolNode());

								return $this;
							}
							else
								throw new LanguageBridgeException("Can't execute (ProtocolNode 0xff) the script, the wrapper is not running");
						}
						else
							throw new LanguageBridgeException("Can't sendbind Protocol Nodes, the wrapper is not running");
					}
					else
						throw new LanguageBridgeException("Can't send script filename (ProtocolNode 0x00)");
				}
				else
					throw new LanguageBridgeException("{$this->Filename} does not exist");
			}
			else
				throw new LanguageBridgeException("Can't execute {$this->Language}");
		}

		private function GetTask()
		{
			$Line = fgets($this->Process->GetStdOut());

			if(empty($Line))
				$Line = fgets($this->Process->GetStdErr());

			if(!empty($Line))
			{
				$ProtocolNode = json_decode(trim($Line));

				if(!empty($ProtocolNode) && is_object($ProtocolNode))
				{
					if(isset($ProtocolNode->Type) && is_int($ProtocolNode->Type))
					{
						if(isset($ProtocolNode->Data) && is_object($ProtocolNode->Data))
						{
							$ProtocolNode = new ProtocolNode($ProtocolNode->Type, (array) $ProtocolNode->Data);

							return $ProtocolNode;
						}
						else
							throw new LanguageBridgeException('ProtocolNode.Data must be an array');
					}
					else
						throw new LanguageBridgeException('ProtocolNode.Type must be an integer');
				}
				else
					throw new LanguageBridgeException('ProtocolNode must be a JSON encoded array');
			}

			return false;
		}

		public function ExecuteTask()
		{
			$ProtocolNode = $this->GetTask();

			if($ProtocolNode !== false)
			{
				if($ProtocolNode->GetType() == 0x21)
				{
					$ResponseNode = $this->Functions[$ProtocolNode->GetData()['Name']]->Call($ProtocolNode->GetData()['Arguments']);

					$EncodedJson = json_encode($ResponseNode->GetProtocolNode());

					return fwrite($this->Process->GetStdIn(), $EncodedJson) === strlen($EncodedJson);
				}
				// Switch, add default
			}

			return false;
		}

		public function IsRunning()
		{ return $this->Process->IsRunning(); }

		public function Close()
		{ return $this->Process->Close(); }

		public function Terminate($Signal = 15)
		{ return $this->Process->Terminate($Signal); }
	}