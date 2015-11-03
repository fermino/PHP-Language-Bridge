<?php
	require_once __DIR__ . '/LanguageBridgeException.php';

	$__Throw = function($Code = null)
	{ throw new Exception("Can't decode protocol node" . (!empty($Code) ? sprintf('(0x%x)', $Code) : null)); };

	$__Process = array();

	while(true)
	{
		$__ProtocolNode = fgets(STDIN);

		if(!empty($__ProtocolNode))
		{
			$__ProtocolNode = json_decode($__ProtocolNode, true);

			if(isset($__ProtocolNode['Type']) && isset($__ProtocolNode['Data']) && is_array($__ProtocolNode['Data']))
			{
				switch($__ProtocolNode['Type'])
				{
					# Config

					case 0x00: // Filename
						if(!empty($__ProtocolNode['Data']['Filename']))
							$__Process['Filename'] = $__ProtocolNode['Data']['Filename'];
						else
							$__Throw($__ProtocolNode['Type']);
						break;

					# Bridge

					case 0x10: // Variable
						if(!empty($__ProtocolNode['Data']['Name']) && is_string($__ProtocolNode['Data']['Name']) && in_array('Value', array_keys(get_defined_vars()['__ProtocolNode']['Data'])))
							${$__ProtocolNode['Data']['Name']} = $__ProtocolNode['Data']['Value'];
						else
							$__Throw($__ProtocolNode['Type']);
						break;

					# Execute

					case 0xff: // Go go go
						break 2;

					default:
						$__Throw($__ProtocolNode['Type']);
						break;
				}
			}
			else
				$__Throw();
		}
	}

	unset($__Throw, $__ProtocolNode);

	require $__Process['Filename'];