<?php
	require_once __DIR__ . '/Process.php';

	require_once __DIR__ . '/LanguageBridgeException.php';

	class LanguageManager
	{
		private static $Languages = array();

		private static function Load()
		{
			if(empty(self::$Languages))
			{
				self::$Languages = array();

				$Languages = array_filter(glob('Languages/*.json'), function($Filename) { return is_file($Filename); });

				foreach($Languages as $LanguagePath)
				{
					$Language = file_get_contents($LanguagePath);

					if(!empty($Language))
					{
						$Language = json_decode($Language, true);

						if(!empty($Language))
						{
							if(empty($Language['Name']))
								throw new LanguageBridgeException("{$LanguagePath}[Name] must not be empty");

							if(empty($Language['Command']))
								throw new LanguageBridgeException("{$LanguagePath}[Command] must not be empty");

							if(empty($Language['Extension']))
								throw new LanguageBridgeException("{$LanguagePath}[Extension] must not be empty");

							if(empty($Language['VersionArgument']))
								throw new LanguageBridgeException("{$LanguagePath}[VersionArgument] must not be empty");

							if(empty($Language['VersionRegex']))
								throw new LanguageBridgeException("{$LanguagePath}[VersionRegex] must not be empty");

							if(empty($Language['ExecuteArguments']))
								throw new LanguageBridgeException("{$LanguagePath}[ExecuteArguments] must not be empty");

							self::$Languages[$Language['Name']] = array
							(
								'Command'          => $Language['Command'],
								'Installed'        => false,
								'Version'          => null,
								'VersionArgument'  => $Language['VersionArgument'],
								'VersionRegex'     => $Language['VersionRegex'],
								'Wrapper'          => 'Wrappers' . DIRECTORY_SEPARATOR . $Language['Name'] . DIRECTORY_SEPARATOR . 'LanguageBridge' . '.' . $Language['Extension'],
								'ExecuteArguments' => $Language['ExecuteArguments']
							);
						}
						else
							throw new LanguageBridgeException("{$LanguagePath} must be a JSON encoded array");
					}
					else
						throw new LanguageBridgeException("{$LanguagePath} must be a JSON encoded array");
				}

				self::CheckLanguages();
			}
		}

		private static function CheckLanguages()
		{
			foreach(self::$Languages as &$Language)
			{
				$Process = new Process($Language['Command'], false, false);

				if($Process->Execute($Language['VersionArgument']))
				{
					$Limit = time() + 1;

					while(time() < $Limit)
					{
						$Line = fgets($Process->GetStdOut());

						if(empty($Line))
							$Line = fgets($Process->GetStdErr());

						if(!empty($Line) && preg_match($Language['VersionRegex'], $Line, $Match))
						{
							$Language['Installed'] = true;

							$Language['Version'] = $Match[1];

							break;
						}

						usleep(100000); // sleeps .1 second, this avoid overloading the proc because the stream is non blocking
					}
				}
			}
		}

		public static function Execute($Language)
		{
			self::Load();

			if(self::IsInstalled($Language))
			{
				$Process = new Process(self::$Languages[$Language]['Command'], false, false);

				$Arguments = str_replace('[FILENAME]', 'Wrappers' . DIRECTORY_SEPARATOR . $Language . DIRECTORY_SEPARATOR . 'LanguageBridge' . self::$Languages[$Language]['Extension'], self::$Languages[$Language]['ExecuteArguments']);

				return $Process->Execute(...$Arguments);
			}

			throw new LanguageBridgeException("{$Language} is not installed");
		}

		public static function GetInstalledLanguages()
		{
			return array_keys(array_filter(self::$Languages, function($Language) { return $Language['Installed']; }));
		}

		public static function IsInstalled($Language)
		{
			return in_array($Language, self::GetInstalledLanguages());
		}
	}