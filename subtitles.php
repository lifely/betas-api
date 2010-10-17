<?php

/*
**
** Stated : 17/10/2010 2:30 - Frenchlabs
** Updated: 17/10/2010 4:30 - Frenchlabs
**
** Writtend by Julien Di Marco <juliendimarco@me.com>
**
*/

require_once('class/betaserie.php');

checkSubs('/home/transmission/Downloads/Tv\ Show/', 'globiboulga', 1);

function checkSubs($directory, $key, $down = 0)
{
	$api = new betaSerie($key);
	$list = glob($directory.'*');

	foreach($list as $path)
	{
		$show = substr($path, (strlen($directory) - 1));
		$showApi = $api->shows()->search($show);
		// print_r($showApi);
		echo "Tv Show : ".$show."\n";
		if (isset($showApi, $showApi['root'], $showApi['root']['shows']) &&
		 	count($showApi['root']['shows']))
			{
				$i = ($show === 'House' ? 8 : 0);
				$i = ($show === 'Nikita' ? 1 : $i);
				$showUrl = $showApi['root']['shows'][$i]['url'];
		
				// echo "\n\n"; print_R($fileList); echo "\n\n";
				
				$subApi = $api->subtitles()->last($showUrl, null, 'VF');
				if (isset($subApi, $subApi['root'], $subApi['root']['subtitles']))
				{
					foreach ($subApi['root']['subtitles'] as $value)
					{
						$fileList = glob($path.'/*');
						// echo '/^subtitle - S'.str_pad($value['season'], 2, '0', STR_PAD_LEFT).'E'.str_pad($value['episode'], 2, '0', STR_PAD_LEFT).'\.zip$/'."\n";
						if ($value['episode'] && $value['source'] === 'tvsubtitles' &&
						 	!(preg_grep('/subtitle - S'.str_pad($value['season'], 2, '0', STR_PAD_LEFT).'E'.str_pad($value['episode'], 2, '0', STR_PAD_LEFT).'\.zip$/', $fileList)) &&
							preg_grep('/S'.str_pad($value['season'], 2, '0', STR_PAD_LEFT).'E'.str_pad($value['episode'], 2, '0', STR_PAD_LEFT).'.*\.(avi|mkv)$/', $fileList))
						{
							echo "Download SubTitle Season ".$value['season']." Episode ".$value['episode']." --> [".$value['file']."]\n";
							if ($down)
								download($value['url'], $path, str_pad($value['season'], 2, '0', STR_PAD_LEFT), str_pad($value['episode'], 2, '0', STR_PAD_LEFT));
						}
					}
					// print_r($subApi);
				}
			}
		echo "\n";
		// exit;
	}
}

function download($link, $path, $season, $episode)
{
	exec('wget -q -O "'.$path.'/subtitle - S'.$season.'E'.$episode.'.zip" "'.$link.'"');
}


