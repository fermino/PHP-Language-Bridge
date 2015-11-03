<?php
	require_once __DIR__ . '/Process.php';

	require_once __DIR__ . '/LanguageException.php';

	class LanguageManager
	{
		protected $Languages = array
		(
			'lua' => array
			(
				'Command' => 'lua',
				'Installed' => false,
				'Version' => null,
				'VersionArgument' => '-v',
				'VersionRegex' => '/Lua (.*)  Copyright \(C\)/',
				'Wrapper' => 'Lua/LanguageBridge.lua',
				'ExecuteArguments' => array
				(
					'[[FILENAME]]'
				)
			),
			'php' => array
			(
				'Command' => 'php',
				'Installed' => false,
				'Version' => null,
				'VersionArgument' => '-v',
				'VersionRegex' => '/PHP (.*) \(cli\)/',
				'Wrapper' => 'PHP/LanguageBridge.php',
				'ExecuteArguments' => array
				(
					'[[FILENAME]]'
				)
			),
			'python' => array
			(
				'Command' => 'python',
				'Installed' => false,
				'Version' => null,
				'VersionArgument' => '--version',
				'VersionRegex' => '/Python (.*)/',
				'Wrapper' => 'Python/LanguageBridge.py',
				'ExecuteArguments' => array
				(
					'[[FILENAME]]'
				)
			)
		);

		public function __construct()
		{
			foreach($this->Languages as &$Language)
			{
				$LanguageProcess = new Process($Language['Command']);//, false, false);

				if($LanguageProcess->Execute($Language['VersionArgument']))
				{
					$EndTime = time() + 1;

					while(time() < $EndTime)
					{
						$Line = fgets($LanguageProcess->GetStdOut());

						if(empty($Line))
							$Line = fgets($LanguageProcess->GetStdErr());

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

		public function Execute($Language)
		{
			if($this->IsInstalled($Language))
			{
				$Process = new Process($this->Languages[$Language]['Command'], false, false);

				$Arguments = str_replace('[[FILENAME]]', 'Wrappers/' . $this->Languages[$Language]['Wrapper'], $this->Languages[$Language]['ExecuteArguments']);

				return $Process->Execute(...$Arguments);
			}

			throw new LanguageException("{$Language} language is not installed");
		}

		public function GetInstalledLanguages()
		{
			return array_keys(array_filter($this->Languages, function($Language) { return $Language['Installed']; }));
		}

		public function IsInstalled($Language)
		{
			return in_array($Language, $this->GetInstalledLanguages());
		}
	}