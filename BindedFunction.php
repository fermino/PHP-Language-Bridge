<?php
	require_once __DIR__ . '/ProtocolNode.php';

	class BindedFunction extends ProtocolNode
	{
		private $Name = null;
		private $Function = null;

		public function __construct($Name, callable $Function)
		{
			$this->Name = $Name;
			$this->Function = $Function;

			parent::__construct(0x11, array('Name' => $Name));
		}

		public function GetName()
		{ return $Name; }

		public function Call(Array $Arguments = array())
		{
			$Function = $this->Function;

			$Return = $Function(...$Arguments);

			return new ProtocolNode(0x31, array('ReturnValue' => $Return));
		}
	}