<?php

require_once __DIR__ . '/global.php';

class OpenGraphStory {
	protected $request;
	private $openGraphStoryParameters = array();

	/**
	 * Constructor
	 */
	public function __construct($request = null) {
		GLOBAL $fb_app_name;
		$this->ugpClient = new UgpClient();
		$this->games = new GameRepository();
		$this->request = $request;
		$this->fb_app_name = $fb_app_name;
		$this->fbAccessToken = $this->ifVariableSet($request['fbAccessToken'], '');
		$this->feed = false;
		$this->debug = $this->ifVariableSet($request['debug'], 'false');
		$this->serverName = $this->ifVariableSet($_SERVER['SERVER_NAME'], 'high5casino.net');
		$this->openGraphStoryName = $this->ifVariableSet($request['openGraphStoryName'], 'bigWin');
		$this->bigWinType = $this->ifVariableSet($request['bigWinType'], 'bigWinBonus');
		$this->actionName = $this->ifVariableSet($request['actionName'], 'reach');
		$this->gameId = $this->ifVariableSet($request['gameId'], 1192);
		$this->gameLevel = $this->ifVariableSet($request['gameLevel'], 1);
		$this->creditsBet = $this->ifVariableSet($request['creditsBet'], 100);
		$this->creditsWon = $this->ifVariableSet($request['creditsWon'], 10000);
		$this->creditsWon = number_format(floor($this->creditsWon/100));
		$this->username = $this->ifVariableSet($request['username'], 'High 5 Games');
		$this->platform = $this->ifVariableSet($request['platform'], 'canvas');
		$this->tier = $this->ifVariableSet($request['tier'], 0);
		$this->vipRewardProgram = $this->ifVariableSet($request['vipRewardProgram'], 0);
		$this->amountWon = $this->ifVariableSet($request['amountWon'], 200);
		$this->vipPoints = $this->ifVariableSet($request['vipPoints'], 100);
		$this->progressiveType = $this->ifVariableSet($request['progressiveType'], 'noProgressiveTypeDefined');
		$this->amountCollected = $this->ifVariableSet($request['amountCollected'], 0);
		$this->milestoneName = $this->ifVariableSet($request['milestoneName'], 'Star');

		$this->appId = $this->ifVariableSet($request['appId'], '');
		$this->shareCode = $this->ifVariableSet($request['shareCode'], '');
		$this->shareType = $this->ifVariableSet($request['shareType'], '');
		$this->userId = $this->ifVariableSet($request['userId'], 0);

		$this->dateTime = new DateTime();
	}
	/**
	 * Is a post call
	 *
	 * @return void
	 */
	public function isPostRestCall() {
		if ($_SERVER['REQUEST_METHOD'] == 'POST' || $this->request['method'] == 'POST') {
			return true;
		}

		return false;
	}

	/**
	 * Choose an open Graph Story
	 *
	 * @return void
	 */
	public function chooseStory($openGraphStoryName) {
		$this->baseUrl = "https://$this->serverName/api/fb/objects";
		switch ($openGraphStoryName) {
		case 'vipTierLevelUp':
			$this->setVipTierLevelUpStory();
			break;
		case 'receivedGifts':
			$this->setReceivedGiftsStory();
			break;
		case 'bigWin':
			$this->setBigWinStory();
			break;
		case 'progressivesWin':
			$this->setProgressiveStory();
			break;
		case 'milestone':
			$this->setMilestone();
			break;
		default:
			$this->openGraphStoryParameters['hf_game'] =
			"https://$this->serverName/api/fb/objects/game.php?game_id=" . $this->gameId;
			$this->openGraphStoryParameters['fb:explicitly_shared'] = true;
			err('MOBILE POST OPEN GRAPH STORY - Open Graph Story NOT IDENTIFIED : ' . $openGraphStoryName);
			break;
		}
	}

	/**
	 * set Setters Story
	 *
	 * @return void
	 */
	public function setMilestone() {
		$game = $this->games->findById($this->gameId);
		$this->actionName = 'earn';
		$this->openGraphStoryParameters['milestone'] =
		$this->baseUrl . "/milestone.php?gameId=" . $this->gameId . "&milestoneName=" . $this->milestoneName
		. "&winAmount=" . $this->amountWon . "&vipPoints=" . $this->vipPoints;
		$this->openGraphStoryParameters['amount_won'] = $this->amountWon;
		$this->openGraphStoryParameters['milestone_name'] = $this->milestoneName;
		$this->openGraphStoryParameters['vip_points'] = $this->vipPoints;
		$this->openGraphStoryParameters['game_name'] = $game->getTitle();
		$this->openGraphStoryParameters['fb:explicitly_shared'] = true;
	}

	/**
	 * set Received Gifts Story
	 *
	 * @return void
	 */
	public function setVipTierLevelUpStory() {
		$this->feed = true;
		$this->actionName = 'become_vip_tier';
		$this->openGraphStoryParameters['vip_tier'] =
		$this->baseUrl . "/vip_tier.php?tier=" . $this->tier . "&username=" . $this->username;
		$this->openGraphStoryParameters['vip_tier_name'] = $this->username;
		$this->openGraphStoryParameters['vip_reward_program'] = $this->vipRewardProgram;
		$this->openGraphStoryParameters['link'] =
		$this->baseUrl . "/vip_tier.php?tier=" . $this->tier . "&username=" . $this->username;
		$this->openGraphStoryParameters['caption'] = "";
		$this->openGraphStoryParameters['fb:explicitly_shared'] = true;
	}

	/**
	 * set Received Gifts Story
	 *
	 * @return void
	 */
	public function setReceivedGiftsStory() {
		$this->actionName = 'receive';
		$this->openGraphStoryParameters['gift'] = $this->baseUrl . "/gift.php";
		$this->openGraphStoryParameters['amount_collected'] = number_format(floor($this->amountCollected / 100));
		$this->openGraphStoryParameters['fb:explicitly_shared'] = true;
	}

	/**
	 * set Progressive Story
	 *
	 * @return void
	 */
	public function setProgressiveStory() {
		$this->actionName = $this->progressiveType;
		$this->openGraphStoryParameters['progressive'] =
		$this->baseUrl . "/progressive.php?progressiveType="
		. $this->progressiveType . "&winAmount=" . number_format(floor($this->amountWon / 100));
		$this->openGraphStoryParameters['hf_game'] =
		$this->baseUrl . "/game.php?game_id=" . $this->gameId;
		$this->openGraphStoryParameters['amount_won'] = number_format(floor($this->amountWon / 100));
		$this->openGraphStoryParameters['fb:explicitly_shared'] = true;
	}

	/**
	 * set Big Win Story
	 *
	 * @return void
	 */
	public function setBigWinStory() {
		global $fb_app_name;
		$this->actionName = ($this->bigWinType === 'bigWinBonus') ? 'win_big' : 'win_big_spin';
		$hf_game = $this->baseUrl . "/game.php?game_id="
		. $this->gameId . "&storyAction=customVideoBigWin";
		$this->openGraphStoryParameters['hf_game'] =
		$this->baseUrl . "/game.php?game_id=" . $this->gameId
		. "&storyAction=customVideoBigWin&playerName=" . rawurlencode($this->username)
		. "&winAmount=" . rawurlencode($this->creditsWon) . "&bigWinText="
		. rawurlencode('Big Win!') . "&bigWinType=" . rawurlencode($this->bigWinType)
		. "&uniquePost=cachebuster-" . $this->dateTime->format('YmdHis')
		. "&link=" . rawurlencode("https://apps.facebook.com/{$fb_app_name}/");
		$this->openGraphStoryParameters['link'] =
		$hf_game . "&storyAction=customVideoBigWin&playerName=" . rawurlencode($this->username)
		. "&creditsWon=" . rawurlencode($this->creditsWon) . "&bigWinText="
		. rawurlencode('Big Win!') . "&bigWinType=" . rawurlencode($this->bigWinType)
		. "&uniquePost=cachebuster-" . $this->dateTime->format('YmdHis')
		. "&link=" . rawurlencode("https://apps.facebook.com/{$fb_app_name}/");
		$this->openGraphStoryParameters['creditsBet'] = $this->creditsBet;
		$this->openGraphStoryParameters['amount_won'] = $this->creditsWon;
		$this->openGraphStoryParameters['caption'] = $this->username;
		$this->openGraphStoryParameters['bigWinType'] = $this->bigWinType;
		$this->openGraphStoryParameters['fb:explicitly_shared'] = true;
	}

	/**
	 * POST A STORY
	 *
	 * @return $output
	 */
	public function post() {
		if ($this->isPostRestCall() === false) {
			return json_encode(array('status' => 'false', 'log' => 'Not a POST Api Call'));
		}

		$this->chooseStory($this->openGraphStoryName);
		$this->openGraphStoryParameters['access_token'] = $this->fbAccessToken;
		$this->openGraphStoryParameters['method'] = 'POST';
		if ($this->feed === true) {
			$url = "https://graph.facebook.com/me/feed";
		} else {
			$url = "https://graph.facebook.com/me/$this->fb_app_name:$this->actionName";
		}

		$output = $this->executeCurl($url, $this->openGraphStoryParameters);
		$decoded = json_decode($output['response']);

		$info = array();
		$info['status'] = (!isset($decoded->error)) ? 'true' : 'false';
		$info['requestId'] = (isset($decoded->id)) ? $decoded->id : 0;
		$info['error'] = (isset($decoded->error)) ? $decoded->error : null;
		$info['story_params'] = $this->openGraphStoryParameters;
		if ($this->debug === 'true') {
			$info['request'] = $this->request;
		}

		err('MOBILE POST OPEN GRAPH STORY ' . json_encode($info));

		return json_encode($info);
	}

	/**
	 * Execute CURL
	 *
	 * @return $output
	 */
	public function executeCurl($web_service_url, $fields) {
		$fields_string = http_build_query($fields);
		//open connection
		$ch = curl_init();
		//set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $web_service_url);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//Execute POST
		$output['response'] = curl_exec($ch);
		$output['info'] = curl_getinfo($ch);
		//close connection
		curl_close($ch);
		return $output;
	}

	/**
	 * Helper function if variable SET
	 *
	 * @return variable
	 */
	public function ifVariableSet(&$variable, $defaultValue = null) {
		if (isset($variable)) {
			return $variable;
		} else {
			return $defaultValue;
		}

	}

	/**
	 * Gets the value of request.
	 *
	 * @return mixed
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Sets the value of request.
	 *
	 * @param mixed $request the request
	 *
	 * @return self
	 */
	protected function setRequest($request) {
		$this->request = $request;
		return $this;
	}
}
