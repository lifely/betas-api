<?php

/*
**
** Stated : 14/10/2010 22:36 - Frenchlabs
** Updated: 14/10/2010 22:36 - Frenchlabs
**
** Writtend by Julien Di Marco <juliendimarco@me.com>
**
*/

/*
** betaSeries Class
*/

$api = new betaSerie('c2fe59daa95d');

$return = $api->subtitles()->last('chuck');

echo '<pre>'; print_r($return); echo '</pre>';
class betaSerie {
	private $domain = 'api.betaseries.com';
	
	private $key;
	private $token;
	private $format;
	private $cat;
	
	private $type = array(
			'shows',
			'subtitles',
			'planning',
			'members',
			'comments',
			'timeline'
			);
	
	public function __construct($key, $format = 'json')
	{
		$this->setKey($key);
		$this->setFormat($format);
	}
	
	private function fetchMultiple($urls, $callback = null, $user_options = null) 
	{
	    // Max Number of query simultaneously - Prefere slow number
	    $rolling_window = 2000;
	    $rolling_window = (count($urls) < $rolling_window) ? count($urls) : $rolling_window;

		$running = 1;

	    $master = curl_multi_init();
	    $curl_arr = array();
		$final = array();

	    // add additional curl options here
	    $curl_options = array(
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_MAXREDIRS => 50,
				CURLOPT_TIMEOUT => 60,
				CURLOPT_CONNECTTIMEOUT => 60,
				CURLOPT_HEADER => false,
				CURLOPT_AUTOREFERER => true,
	    		CURLOPT_RETURNTRANSFER => true,
	    		CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)'
							);

	    $options = ($user_options) ? ($curl_options + $user_options) : $curl_options;

	    // start the first batch of requests
	    for ($i = 0; $i < $rolling_window; $i++) 
		{
	        $ch = curl_init();
	        $options[CURLOPT_URL] = $urls[$i];
	        curl_setopt_array($ch, $options);
	        curl_multi_add_handle($master, $ch);
	    }

	    while ($running)
	 	{
			// Wait Curl launch
	        while(($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM);
	        if($execrun != CURLM_OK)
	            break;

	        // a request was just completed -- find out which one
	        while($done = curl_multi_info_read($master))
			{
	            $info = curl_getinfo($done['handle']);
	            if ($info['http_code'] == 200)  
				{
	                $output = curl_multi_getcontent($done['handle']);

	                // request successful.  process output using the callback function.
					if ($callback && is_callable($callback))
	                	$callback($info, $output);

	                // start a new request (it's important to do this before removing the old one)
					if ($i < count($urls))
					{
	                	$ch = curl_init();
	                	$options[CURLOPT_URL] = $urls[$i++];  // increment i
	                	curl_setopt_array($ch, $options);
	                	curl_multi_add_handle($master, $ch);
					}

					// Build entry to the final array
					$final = array(
							'info' => $info,
							'content' => $output
							);

	                // remove the curl handle that just completed
	                curl_multi_remove_handle($master, $done['handle']);
					curl_close($done['handle']);
	            } 
				else
					echo 'Error';
	                // request failed.  add error handling.
	        }
	    }

	    curl_multi_close($master);
	    return true;
	}
	
	private function request($function, $options = null)
	{
		$option = array(
			'key' => $this->key
			);
			
		$option = ($options) ? ($option + $options) : $option;
		
		$url = $this->domain.'/'.$this->type[$this->cat].'/'.$function.'.'.$this->format;
		if (isset($option) && $option)
			$url .= '?'.http_build_query($option);
		return $this->fetch($url);
	}
	
	private function fetch($url, $option = null, $post = null)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_MAXREDIRS,50);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");	
		if ($post)
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
		}	
		$output = curl_exec($ch);
		curl_close($ch);
		return $this->interpreter($output);
	}
	
	private function interpreter($api)
	{
		if ($this->format === 'json')
			return json_decode($api, true);
		else if ($this->format === 'xml')
			return  simplexml_load_string($api);
	}


	public function episodes($url, $season = null)
		{
			if ($this->cat === 0)
				return $this->request('episodes/'.$url, array('season' => $season));
			else if ($this->cat === 3)
				return $this->request('episodes/'.$url, array('token' => $this->token));
		}
	
	// Function Prio Show - 
	public function shows()
	{
		$this->setCat(0);
		return $this;
	}
	
		public function search($title)
		{
			if ($this->cat === 0)
				return $this->request('search', array('title' => $title));
		}
	
		public function display($url)
		{
			if ($this->cat === 0)
				return $this->request('display/'.$url);
		}
	
		public function add($url)
		{
			if ($this->cat === 0)
				return $this->request('add/'.$url, array('token' => $this->token));
		}
	
		public function remove($url)
		{
			if ($this->cat === 0)
				return $this->request('remove/'.$url, array('token' => $this->token));
		}
	
	// Function Prio Subtitles -
	public function subtitles()
	{
		$this->setCat(1);
		return $this;
	}

		public function last($url = null, $number = null, $language = null)
		{
			if ($this->cat === 1)
			{
				$array = array();
				if (isset($number) && $number)
					$array = array_merge($array, array('number' => $number));
				if (isset($language) && $language)
					$array = array_merge($array, array('language' => $language));
				return $this->request('last'.($url ? '/'.$url : ''), $array);
			}
		}

		public function show($url = null, $language = null, $season = null, $episode = null)
		{
			if ($this->cat === 1)
			{
				$array = array();
				if (isset($season) && $season)
					$array = array_merge($array, array('season' => $season));
				if (isset($language) && $language)
					$array = array_merge($array, array('language' => $language));
				if (isset($episode) && $episode)
					$array = array_merge($array, array('episode' => $episode));
				return $this->request('show'.($url ? '/'.$url : ''), $array);
			}
			else if ($this->cat === 4)
				return $this->request('show/'.$url);
		}

	// Function Prio Planning
	public function planning()
	{
		$this->setCat(2);
		return $this;
	}
	
		public function general()
		{
			if ($this->cat === 2)
				return $this->request('general');
		}
		
		public function member($login = null, $view = 0)
		{
			if ($this->cat === 2)
			{
				$array = array();
				if (isset($view) && $view)
					$array = array_merge($array, array('view' => unseen));
				if (isset($login) && !($login))
					$array = array_merge($array, array('token' => $this->token));
				return $this->request('member'.($url ? '/'.$login : ''), $array);
			}
			else if ($this->cat === 4)
			{
				$array = array();
				if (!($login))
					$array = array('token' => $this->token);
				return $this->request('member'.($login ? '/'.$login : ''), $array);
			}
			else if ($this->cat === 5)
			{
				$array = array('token' => $token);
				if (isset($view) && $view)
					$array = array_merge($array, array('number' => $view));
				return $this->request('home/'.$login, $array);
			}
		}
	
	// Function Prio Members
	public function members()
	{
		$this->setCat(3);
		return $this;
	}
	
		public function auth($login, $password, $md5 = 0)
		{
			if ($this->cat === 3)
				return $this->request('auth', array('login' => $login, 'password' => ($md5 ? md5($password) : $password)));
		}
	
		public function destroy($token = null)
		{
			if ($this->cat === 3)
				return $this->request('destroy', array('token' => ($token ? $this->token : $token)));
		}
		
		public function infos($login = null)
		{
			if ($this->cat === 3)
			{
				$array = array();
				if (isset($login) && !($login))
					$array = array('token' => $this->token);
				return $this->request('infos'.($login ? '/'.$login : ''), $array);
			}
		}

		public function watched($url, $season, $episode)
		{
			if ($this->cat === 3)
				return $this->request('watched/'.$url, array('token' => $this->token, 'season' => $season, 'episode' => $episode));
		}
	
		public function notifications($seen = null, $number = null, $id = null)
		{
			if ($this->cat === 3)
			{
				$array = array('token' => $this->token);
				
				$array = ((isset($seen) && $seen) ? array_merge($array, array('seen' => ($seen ? 'yes' : 'no'))) : $array);
				$array = ((isset($number) && $number) ? ($array + array('number' => $number)) : $array);
				$array = ((isset($id) && $id) ? ($array + array('last_id' => $id)) : $array);
				return $this->request('notifications', $array);
			}
		}

	// Function Prio Comments
	public function comments()
	{
		$this->setCat(4);
		return $this;
	}
		
		public function episode($url, $season, $episode)
		{
			if ($this->cat === 4)
				return $this->request('episode/'.$url, array('season' => $season, 'episode' => $episode));
		}
	
	public function timeline()
	{
		$this->setCat(5);
		return $this;
	}
	
		public function home($number = null)
		{
			if ($this->cat === 5)
			{
				$array = array();
				if (isset($number) && $number)
					$array = array('number' => $number);
				return $this->request('home', $array);
			}
		}
		
		public function friends($number = null)
		{
			if ($this->cat === 5)
			{
				$array = array();
				if (isset($number) && $number)
					$array = array('number' => $number);
				return $this->request('friends', $array);
			}
		}
		
	// set & get function.
	public function setDomain($domain)
	{
		$this->domain = $domain;
	}

	public function setKey($key)
	{
		$this->key = $key;
	}
	
	public function setToken($token)
	{
		$this->token = $key;
	}
	
	public function setCat($cat)
	{
		$this->cat = $cat;
	}
	
	public function setFormat($format)
	{
		$this->format = $format;
	}
	
	public function getDomain()
	{
		return $this->domain;
	}
	
	public function getKey()
	{
		return $this->key;
	}
	
	public function getToken()
	{
		return $this->token;
	}
	
	public function getFormat()
	{
		return $this->format;
	}
	
	public function getCat($format = 0)
	{		
		if ($format)
			return $this->cat;
		else
			return $this->type[$this->cat];
	}
}