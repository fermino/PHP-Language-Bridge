<?php
	require_once __DIR__ . '/ProtocolNode.php';

	class BindedVariable extends ProtocolNode
	{
		private $Name = null;
		private $Value = null;

		public function __construct($Name, $Value)
		{
			$this->Name = $Name;
			$this->Value = $Value;

			parent::__construct(0x10, array('Name' => $Name, 'Value' => $Value));
		}

		public function GetName()
		{ return $Name; }

		public function GetValue()
		{ return $Value; }
	}