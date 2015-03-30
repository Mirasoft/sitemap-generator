<?php
DEFINE('DIR', dirname(__FILE__));

Class sitemap{
	protected $_host = ''; // хост
	protected $_scheme = 'http'; // протокол
	
	protected $_links = array();
	
	public $_nesting = 0; //максимальный уровень вложености, 0 - не ограничено
	public $_limit = 0; // максимальное количество ссылок, 0 - не ограничено
	public $_sort = false; // сортировать по уровню вложености
	public $_gzip = false; // Gzip сжатие
	public $_formatOutput = false; // Форматирование разметки файла
	
	public $_changefreq = false; // устанавливать частоту обновления
	public $_modification = false; // устанавливать время последней модификации
	public $_priority = false; // устанавливать приоритет страниц
	
	protected $_session = 0;
	public $_cookie = true; // использовать куки
	
	protected $_useragents = array();
	protected $_useragent = false;
	
	protected $_timeout = 2; // время ожидания
	
	public function __construct(){
		// подгрузка конфига
		if(file_exists(DIR.'/config.php')){
			require(DIR.'/config.php');
			
			if(isset($config) && is_array($config)){
				if(array_key_exists('cookie', $config) && is_bool($config['cookie'])){
					$this->_cookie = $config['cookie'];
				}
				
				if(!empty($config['useragents']) && is_array($config['useragents'])){
					$this->_useragents = $config['useragents'];
				}
				
				if(array_key_exists('timeout', $config) && is_int($config['timeout'])){
					$this->_timeout = $config['timeout'];
				}
				
				if(array_key_exists('nesting', $config) && is_int($config['nesting'])){
					$this->_nesting = $config['nesting'];
				}
				
				if(array_key_exists('limit', $config) && is_int($config['limit'])){
					$this->_limit = $config['limit'];
				}
				
				if(array_key_exists('sort', $config) && is_bool($config['sort'])){
					$this->_sort = $config['sort'];
				}
				
				if(array_key_exists('gzip', $config) && is_bool($config['gzip'])){
					$this->_gzip = $config['gzip'];
				}
				
				if(array_key_exists('formatOutput', $config) && is_bool($config['formatOutput'])){
					$this->_formatOutput = $config['formatOutput'];
				}
				
				if(array_key_exists('changefreq', $config) && is_bool($config['changefreq'])){
					$this->_changefreq = $config['changefreq'];
				}
				
				if(array_key_exists('modification', $config) && is_bool($config['modification'])){
					$this->_modification = $config['modification'];
				}
				
				if(array_key_exists('priority', $config) && is_bool($config['priority'])){
					$this->_priority = $config['priority'];
				}
			}
			
			$config = null;
		}
	}
	
	/*
	 * Метод для получения содержимого страниц
	 */
	function getCurl($url = null, $main = false, $i = 0){	
		if($url != null){
			$ch = curl_init($url);
			
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_REFERER, $this->_scheme.'://'.$this->_host);
			
			if($this->_useragent){
				curl_setopt($ch, CURLOPT_USERAGENT, $this->_useragent);
			}
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			
			if($this->_cookie){
				curl_setopt($ch, CURLOPT_COOKIEJAR, DIR.'/tmp/'.$this->_session);
				curl_setopt($ch, CURLOPT_COOKIEFILE, DIR.'/tmp/'.$this->_session);
			}
			
			$ret = curl_exec($ch);
			
			$info = curl_getinfo($ch);
			
			if($info){
				if($info['http_code'] !== 200){
					$ret = null;
					
					if($info['redirect_url'] != null){
						$url = parse_url($info['redirect_url']);
						
						// Если указан редирект то проверяется на какой домен
						if(array_key_exists('host', $url)){
							if($url['host'] != $this->_host){
								// Если главная страница и есть редирект то проверить куда (например при добавлении www)
								if($main){
									$tmp = explode('.', $url['host']);
									
									if($tmp[0] != 'www'){
										curl_close($ch);
										return false;
									}else{
										unset($tmp[0]);
										
										$tmp = implode('.', $tmp);
										
										if($tmp != $this->_host){
											curl_close($ch);
											return false;
										}
										
										// установка нового хоста
										$this->_host = 'www.'.$this->_host;
									}
								}else{
									return false;
								}
							}
							
							// установка нового протокола 
							if($url['scheme'] != $this->_scheme){
								$this->_scheme = $url['scheme'];
							}
							
							curl_close($ch);
							
							return $this->getCurl($info['redirect_url']);
						}
					}
					
					$info = null;
				}else{
					// Проверяется тип контента
					if(strpos($info['content_type'], 'text/html') !== false){
						// Проверка содрежимого ответа
						if($ret != null){
							curl_close($ch);
							
							$info = null;
							
							return $ret;
						}else{
							// Количество попыток получения данных
							if($i < 2){
								$i++;
								usleep(500);
								
								// Повторяем попытку
								return $this->getCurl($url, $main, $i);
							}
						}
					}
				}
			}
		}
		
		curl_close($ch);
		
		return false;
	}
	
	/*
	 * Метод очистки куков
	 */
	function clear(){
		if($this->_cookie){
			if(file_exists(DIR.'/tmp/'.$this->_session)){
				unlink(DIR.'/tmp/'.$this->_session);
			}
		}
	}
	
	/*
	 * Метод для валидации и обработки урлов
	 */
	function parse_url($tmp){
		$tmp = parse_url($tmp);
		
		$url = '';
		
		if(!array_key_exists('scheme', $tmp)){
			$scheme = $this->_scheme;
		}else{
			$scheme = $tmp['scheme'];
		}
		
		if(!array_key_exists('path', $tmp)){
			if(array_key_exists('host', $tmp) && $tmp['host'] == $this->_host){
				$url = $scheme.'://'.$this->_host;
			}else{
				return false;
			}
		}else{
			if(!array_key_exists('host', $tmp) || $tmp['host'] == $this->_host){
				$tmp['path'] = str_replace("&amp;", '&', $tmp['path']);
				$tmp['path'] = ltrim($tmp['path'], '/');
				
				if($tmp['path'] != null){
					if(!array_key_exists('query', $tmp)){
						$url = $scheme.'://'.$this->_host.'/'.$tmp['path'];
					}else{
						$url = $scheme.'://'.$this->_host.'/'.$tmp['path'].'?'.$tmp['query'];
					}
				}else{
					if(!array_key_exists('query', $tmp)){
						$url = $scheme.'://'.$this->_host;
					}else{
						$url = $scheme.'://'.$this->_host.'?'.$tmp['query'];
					}
				}
			}
		}
		
		$tmp = null;
		
		if(filter_var($url, FILTER_VALIDATE_URL)){
			return $url;
		}
		
		return false;
	}
	
	/*
	 * Парсинг ссылок из HTML 
	 */
	function parse_links($data){
		preg_match_all('#<a[^<>]*href=[\'"]([^\'"\(\)@]{2,})[\'"][^<>]*>#i', $data, $matches);
		
		if(!empty($matches[1])){
			$links = array();
			
			foreach($matches[1] as $item){
				if(false !== ($item = $this->parse_url($item))){
					$links[] = $item;
				}
			}
			
			if(!empty($links)){
				return array_unique($links);
			}
		}
		return false;
	}
	
	/*
	 * Проход по ссылкам
	 */	
	function get_links($url){
		$lvl = 1;
		$count = 0;
		
		$stop = false;
		
		$errors = array();
		
		if(false !== ($data = $this->getCurl($url, true))){
			$this->_links[$url] = $lvl;
			
			$lvl++;
			$count++;
			
			if(false !== ($data = $this->parse_links($data))){										
				handling:
								
				if($this->_nesting > 0 && $lvl > $this->_nesting){
					$stop = true;
				}
				
				if(!$stop){
					$tmp_links = array();
					
					foreach($data as $item){
						if(!in_array($item, $errors) && !array_key_exists($item, $this->_links)){
							if(false !== ($tmp = $this->getCurl($item))){
								$this->_links[$item] = $lvl;
								$count++;
								
								if($this->_limit > 0 && ($count == $this->_limit)){
									$stop = true;
									break;
								}
								
								if(false !== ($tmp = $this->parse_links($tmp))){
									$tmp_links = array_merge($tmp_links, $tmp);
								}
								
								$tmp = null;
							}else{
								$errors[] = $item;
							}
						}
					}
					$data = null;
									
					if(!empty($tmp_links)){
						$data = array_unique($tmp_links);
						$tmp_links = null;
					}
					
					if(!$stop){
						if(!empty($data)){
							$lvl++;
							
							// Начинаем обработку новых ссылок
							goto handling;
						}
					}
				}
			}
			
			$data = null;
		}
		
		$errors = null;
	}
	
	/*
	 * Метод для генерации XML файла из массива ссылок
	 */
	function genereate_xml(){
		$root = DIR.'/files/';
		$file = 'sitemap_'.time().'.xml';
		
		if(!empty($this->_links)){
			$xml = new DomDocument('1.0','utf-8');
			
			$urlset = $xml->appendChild($xml->createElement('urlset'));
			
			$urlset->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
			$urlset->setAttribute('xsi:schemaLocation','http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
			$urlset->setAttribute('xmlns','http://www.sitemaps.org/schemas/sitemap/0.9');
			
			// Если разрешено выставлять время последней модификации то берется текущее серверное время
			if($this->_modification){
				$date = date('c');
			}
			
			foreach($this->_links as $item => $lvl){
				$url = $urlset->appendChild($xml->createElement('url'));
				
				$loc = $url->appendChild($xml->createElement('loc'));
				$loc->appendChild($xml->createTextNode($item));
				
				// Если разрешено выставлять время последней модификации
				if($this->_modification){
					$lastmod = $url->appendChild($xml->createElement('lastmod'));
					$lastmod->appendChild($xml->createTextNode($date));
				}
				
				// Если разрешено выставлять частоту изминений страницы
				if($this->_changefreq){
					$changefreq = $url->appendChild($xml->createElement('changefreq'));
					$changefreq->appendChild($xml->createTextNode($this->_changefreq));
				}
				
				// Если разрешено автоматически подсчитывать приоритет
				if($this->_priority){
					$p = '0.8';
					
					// Приоритет выставляется в зависимости от уровня вложенности
					if($lvl < 2){
						$p = '1.0';
					}elseif($lvl == 3){
						$p = '0.8';
					}elseif($lvl == 4){
						$p = '0.6';
					}elseif($lvl == 5){
						$p = '0.4';
					}elseif($lvl == 6){
						$p = '0.2';
					}
					
					$priority = $url->appendChild($xml->createElement('priority'));
					$priority->appendChild($xml->createTextNode($p));
				}
			}
			$this->_links = null;
			
			$xml->formatOutput = $this->_formatOutput;
			
			$xml->save($root.'/'.$file);
			
			if(!file_exists($root.'/'.$file)){
				return false;
			}else{
				if(!$this->_gzip){
					return $file;
				}else{
					// Если не удалось создать GZ архив то возвращается не сжатый файл
					if(!$FilePathHandle = gzopen($root.'/'.$file.'.gz','w')) {
						return $file;
					}
					
					// Готовый файл считывается и пакуется в архив
					gzwrite($FilePathHandle, file_get_contents($root.'/'.$file));
					unlink($root.'/'.$file);
					
					return $file.'.gz';
				}
			}
		}
		
		return false;
	}
	
	/*
	 * Начальный метод
	 */
	function run($url = null){
		$url = trim($url);
		
		if($url != null){
			$tmp = parse_url($url);
			
			if($tmp){
				if(!array_key_exists('host', $tmp) && array_key_exists('path', $tmp)){
					$url = $this->_scheme.'://'.$tmp['path'];
					$tmp = parse_url($url);
					
					if(!array_key_exists('host', $tmp)){
						$url = $this->_scheme.'://'.$tmp['host'];
					}
				}else{
					if(array_key_exists('scheme', $tmp) && ($tmp['scheme'] != 'http' && $tmp['scheme'] != 'https')){
						return false;
					}else{
						$this->_scheme = $tmp['scheme'];
					}
					
					$url = $this->_scheme.'://'.$tmp['host'];
				}
				
				if(filter_var($url, FILTER_VALIDATE_URL)){
					$this->_host = $tmp['host'];
					
					// Обнуление старых ссылок
					$this->_links = array();
					
					// Установка id сессии (для файла куков)
					$this->_session = time();
					
					// Установка юзерагента
					if($this->_useragents){
						$this->_useragent = $this->_useragents[array_rand($this->_useragents, 1)];
					}else{
						$this->_useragent = false;
					}
					
					// Получаем ссылки
					$this->get_links($url);
					
					// Чистим старые куки
					$this->clear();
					
					return $this->genereate_xml();
				}
			}
		}
		
		return false;
	}
}
?>
