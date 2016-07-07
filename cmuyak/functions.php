<?php

include "config.php";

/*
 * For all functions $dbh is a database connection
 */

/*
 * @return handle to database connection
 */
function db_connect($host, $port, $db, $user, $pw) { 
	$dbh = pg_connect("host=" . $host . " port=" . $port . " dbname=" . $db . " user=" . $user . " password=" . $pw);
	return $dbh;
}

/*
 * Close database connection
 */ 
function close_db_connection($dbh) {
	pg_close($dbh);
}

/*
 * Login if user and password match
 * Return associative array of the form:
 * array(
 *		'status' =>  (1 for success and 0 for failure)
 *		'userID' => '[USER ID]'
 * )
 */

function login($dbh, $user, $pw) {
	
	$username = pg_escape_string($dbh, $user);
	$password = pg_escape_string($dbh, $pw);
	$query = 'SELECT password FROM users WHERE username=\'' . $username . '\'';
	error_log($query);
	$result = pg_query($dbh, $query);
	$resultArray = pg_fetch_array($result);
	if ($resultArray and ($password == $resultArray['password'])){
		return array('status'=>1, 'userID'=>$username);
	}
	return array('status'=>0, 'userID'=>'');

	
}

/*
 * Register user with given password 
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'userID' => '[USER ID]'
 * )
 */

function register($dbh, $user, $pw) {	
	
	$username = pg_escape_string($dbh, $user);
	$password = pg_escape_string($dbh, $pw);
	#check length
	if (strlen($username) < 2 or strlen($username) > 50){
		return array('status'=>0, 'userID'=>'');
	}
	#check existence
	$checkQuery = 'SELECT password FROM users WHERE username=\'' . $username . '\'';
	error_log($checkQuery);
	$checkResult = pg_query($dbh, $checkQuery);
	$checkResultArray = pg_fetch_array($checkResult);
	if ($checkResultArray){
		return array('status'=>0, 'userID'=>'');
	}
	#add user
	$password = pg_escape_string($dbh, $password);
	$addQuery = 'INSERT INTO users (username, password) VALUES (\'' . $username . '\', \'' . $password . '\')';
	error_log($addQuery);
	$addResult = pg_query($dbh, $addQuery);
	return array('status'=>1, 'userID'=>$username);
	
}

/*
 * Make a new post.
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 		'pID' => new post id
 * )
 */
 
function post_post($dbh, $title, $msg, $coorx, $coory, $me) {
	$title = pg_escape_string($dbh, $title);
	$msg = pg_escape_string($dbh, $msg);
	$coorx = pg_escape_string($dbh, $coorx);
	$coory = pg_escape_string($dbh, $coory);
	$me = pg_escape_string($dbh, $me);
	if (strlen($title) < 2 or strlen($title) > 20 or strlen($msg) < 2 or strlen($msg) > 42){
		return array('status'=>0, 'pID'=>'');
	}
	$query = 'INSERT INTO post (username, title, body, locationx, locationy, ts) VALUES (\'' . $me . '\', \'' . $title . '\', \'' . $msg . '\', ' . $coorx . ', ' . $coory . ', cast(extract(epoch from now()) as BIGINT)) RETURNING postid';
	error_log($query);
	$result = pg_query($dbh, $query);
	$resultArray = pg_fetch_array($result);
	if ($resultArray){
		return array('status'=>1, 'pID'=>$resultArray['postid']);
	} else {
		return array('status'=>0, 'pID'=>'');
	}
	
}

/*
 * Attach a hashtag value to a post. 
 * Optional: If no one has used this hashtag before, also call create_hashtag() to insert into hashtag table.
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 * )
 */

function attach_hashtag($dbh, $pid, $tagname) {
	$pid = pg_escape_string($dbh, $pid);
	$tagname = pg_escape_string($dbh, $tagname);

	$query = 'INSERT INTO hashtag (hashtagname,postid) VALUES (\'' . $tagname . '\', ' . $pid . ')';
	error_log($query);
	$result = pg_query($dbh, $query);
	if ($result == null){
		return array('status'=>0);
	}

	return array('status'=>1);

	

}

/*
 * Create a hashtag if not exists 
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 * )
 */
function create_hashtag($dbh, $tagname) {

}

/*
 * Get all posts with a given hashtag
 * Order by time of the post (going backward in time), and break ties by sorting by the username alphabetically
 * Return associative array of the form:
 * array(
 *		'status' => (1 for success and 0 for failure)
 *		'posts' => [ (Array of post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE OF POST)
 *      'content' => (CONTENT OF POST)
 *		'time' => (UNIXTIME INTEGER)
 *		'coorX' => (coordination x, INTEGER)
 *		'coorY' => (coordination y, INTEGER)
 * )
 */

function search_post_by_tag($dbh, $tagname) {
	$tagname = pg_escape_string($dbh, $tagname);
	$query = 'SELECT * FROM post, hashtag WHERE post.postid=hashtag.postid AND hashtagname=\'' . $tagname . '\' ORDER BY post.ts DESC';
	error_log($query);
	$result = pg_query($dbh, $query);
	$posts = array();
	if ($result){
		while ($resultArray = pg_fetch_array($result)){
			$tmp =  array(
	 		'pID' => $resultArray['postid'],
 			'username' => $resultArray['username'],
 			'title' => $resultArray['title'],
        	'content' => $resultArray['body'],
	 		'time' => $resultArray['ts'],
	 		'coorX' => $resultArray['locationx'],
 			'coorY' => $resultArray['locationy'],
  			);
  			array_push($posts, $tmp);
		}
		return array('status'=>1, 'posts'=>$posts);
	}
	return array('status'=>0, 'posts'=>$posts);
	
}
/*
 * Get timeline of $count most recent posts that were written before timestamp $start
 * For a user $user, the timeline should include all posts.
 * Order by time of the post (going backward in time), and break ties by sorting by the username alphabetically
 * Return associative array of the form:
 * array(
 *		'status' => (1 for success and 0 for failure)
 *		'posts' => [ (Array of post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE OF POST)
 *      'content' => (CONTENT OF POST)
 *		'time' => (UNIXTIME INTEGER)
 *		'coorX' => (coordination x, INTEGER)
 *		'coorY' => (coordination y, INTEGER)
 * )
 */

function get_timeline($dbh, $user, $count = 10, $start = PHP_INT_MAX) {
	$user = pg_escape_string($dbh, $user);
	$query = 'SELECT * FROM post WHERE ts<' . $start . ' ORDER BY ts DESC, username ASC LIMIT ' . $count;
	error_log($query);
	$result = pg_query($dbh, $query);
	$posts = array();
	if ($result){
		while ($resultArray = pg_fetch_array($result)){
			$tmp =  array(
	 		'pID' => $resultArray['postid'],
 			'username' => $resultArray['username'],
 			'title' => $resultArray['title'],
        	'content' => $resultArray['body'],
	 		'time' => $resultArray['ts'],
	 		'coorX' => $resultArray['locationx'],
 			'coorY' => $resultArray['locationy'],
  			);
  			array_push($posts, $tmp);
		}
		return array('status'=>1, 'posts'=>$posts);
	}
	return array('status'=>0, 'posts'=>$posts);

}

/*
 * Get list of $count most recent posts that were written by user $user before timestamp $start
 * Order by time of the post (going backward in time)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'posts' => [ (Array of post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE)
 *		'content' => (CONTENT)
 *		'time' => (UNIXTIME INTEGER)
 *		'coorX' => (coordination x, INTEGER)
 *		'coorY' => (coordination y, INTEGER)
 * )
 */

function get_user_posts($dbh, $user, $count = 10, $start = PHP_INT_MAX) {
	$user = pg_escape_string($dbh, $user);

	$query = 'SELECT * FROM post WHERE username=\'' . $user . '\' AND ts<' . $start . ' ORDER BY ts DESC, username ASC LIMIT ' . $count;
	error_log($query);
	$result = pg_query($dbh, $query);
	$posts = array();
	if ($result){
		while ($resultArray = pg_fetch_array($result)){
			$tmp =  array(
	 		'pID' => $resultArray['postid'],
 			'username' => $resultArray['username'],
 			'title' => $resultArray['title'],
        	'content' => $resultArray['body'],
	 		'time' => $resultArray['ts'],
	 		'coorX' => $resultArray['locationx'],
 			'coorY' => $resultArray['locationy'],
  			);
  			array_push($posts, $tmp);
		}
		return array('status'=>1, 'posts'=>$posts);
	}
	return array('status'=>0, 'posts'=>$posts);
	
}

/*
 * Deletes a post given $user name and $pID.
 * $user must be the one who posted the post $pID.
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success. 0 or 2 for failure)
 * )
 */

function delete_post($dbh, $user, $pID) {
	$user = pg_escape_string($dbh, $user);
	$pID = pg_escape_string($dbh, $pID);
	$query = 'DELETE FROM post WHERE postid=' . $pID . ' AND username=\'' . $user . '\'';
	$result = pg_query($dbh, $query);
	if ($result){
		return array('status'=>1);
	}
	return array('status'=>0);
}

/*
 * Records a "vote" for a post given logged-in user $me and $pID.
 * You don't have to call already_voted() in this function, since it's taken care of by our web application
 * You can assume that when vote_post() is called, $me hasn't yet voted for post with $pID
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success. 0 for failure)
 * )
 */
function vote_post($dbh, $me, $pID) {
	$me = pg_escape_string($dbh, $me);
	$pID = pg_escape_string($dbh, $pID);
	#check postid
	$query = 'SELECT username FROM post WHERE postid =' . $pID;
	$result = pg_query($dbh, $query);
	$resultArray = pg_fetch_array($result);
	if (!$resultArray){
		return array('status'=>0);
	}
	#cannot vote for himself
	if ($resultArray['username'] == $me){
		return array('status'=>0);
	}
	$query = 'INSERT INTO vote (username, postid) VALUES (\'' . $me . '\', ' . $pID . ')';
	$result = pg_query($dbh, $query);
	if (!$result){
		return array('status'=>0);
	}
	return array('status'=>1);
}


/*
 * Records a "unvote" for a post given logged-in user $me and $pID.
 * You don't have to call already_voted() in this function, since it's taken care of by our web application
 * You can assume that when unvote_post() is called, $me has already voted for post with $pID
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success. 0 for failure)
 * )
 */
function unvote_post($dbh, $me, $pID) {
	$me = pg_escape_string($dbh, $me);
	$pID = pg_escape_string($dbh, $pID);
	$query = 'DELETE FROM vote WHERE postid=' . $pID . ' AND username=\'' . $me . '\'';
	$result = pg_query($dbh, $query);
	if (!$result){
		return array('status'=>0);
	}
	return array('status'=>1);
}


/*
 * Check if $me has already voted post $pID
 * Return true if user $me has voted for post $pID or false otherwise
 */
function already_voted($dbh, $me, $pID) {
	$me = pg_escape_string($dbh, $me);
	$pID = pg_escape_string($dbh, $pID);
	#check existence
	$query = 'SELECT * FROM vote WHERE postid=' . $pID . ' AND username=\'' . $me . '\'';
	$result = pg_query($dbh, $query);
	$resultArray = pg_fetch_array($result);
	if ($resultArray){
		return true;
	}
	
	return false;
}


/*
 * Find the $count most recent posts that contain the string $key
 * Order by time of the post and break ties by the username (sorted alphabetically A-Z)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'posts' => [ (Array of Post objects) ]
 * )
 */
function search($dbh, $key, $count = 50) {
	$key = pg_escape_string($dbh, $key);
	
	$query = 'SELECT * FROM post WHERE body LIKE \'%' . $key . '%\' ORDER BY ts DESC, username ASC LIMIT ' . $count;
	error_log($query);
	$result = pg_query($dbh, $query);
	$posts = array();
	if ($result){
		while ($resultArray = pg_fetch_array($result)){
			$tmp =  array(
	 		'pID' => $resultArray['postid'],
 			'username' => $resultArray['username'],
 			'title' => $resultArray['title'],
        	'content' => $resultArray['body'],
	 		'time' => $resultArray['ts'],
	 		'coorX' => $resultArray['locationx'],
 			'coorY' => $resultArray['locationy'],
  			);
  			array_push($posts, $tmp);
		}
		return array('status'=>1, 'posts'=>$posts);
	}
	return array('status'=>0, 'posts'=>$posts);
}

/*
 * Find the $count most recent posts that contain the string $key, and is within the range $range of ($coorX, $coorY)
 * Order by time of the post and break ties by the username (sorted alphabetically A-Z)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'posts' => [ (Array of Post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE)
 *		'content' => (CONTENT)
 *		'time' => (UNIXTIME INTEGER)
 *		'coorX' => (coordination x, INTEGER)
 *		'coorY' => (coordination y, INTEGER)
 * )
 */
function search_range($dbh, $key, $coorx, $coory, $range, $count = 50) {
	$key = pg_escape_string($dbh, $key);
	$coorx = pg_escape_string($dbh, $coorx);
	$coory = pg_escape_string($dbh, $coory);
	$range = pg_escape_string($dbh, $range);
	$query = 'SELECT * FROM post WHERE body LIKE \'%' . $key . '%\' AND power((locationx-' . $coorx . '),2) + power((locationy-' . $coory . '),2) < power(' . $range . ', 2) ORDER BY ts DESC, username ASC LIMIT ' . $count;
	error_log($query);
	$result = pg_query($dbh, $query);
	$posts = array();
	if ($result){
		while ($resultArray = pg_fetch_array($result)){
			$tmp =  array(
	 		'pID' => $resultArray['postid'],
 			'username' => $resultArray['username'],
 			'title' => $resultArray['title'],
        	'content' => $resultArray['body'],
	 		'time' => $resultArray['ts'],
	 		'coorX' => $resultArray['locationx'],
 			'coorY' => $resultArray['locationy'],
  			);
  			array_push($posts, $tmp);
		}
		return array('status'=>1, 'posts'=>$posts);
	}
	return array('status'=>0, 'posts'=>$posts);
}


/*
 * Get the number of votes of post $pID
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'count' => (The number of votes)
 * )
 */
function get_num_votes($dbh, $pID) {
	$pID = pg_escape_string($dbh, $pID);
	$query = 'SELECT count(*) AS num FROM vote WHERE postid=' . $pID;
	error_log($query);
	$result = pg_query($dbh, $query);
	$resultArray = pg_fetch_array($result);
	if (!$resultArray){
		return array('status'=>0);
	}
	return array('status'=>1, 'count'=>$resultArray['num']);
}

/*
 * Get the number of posts of user $uID
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'count' => (The number of posts)
 * )
 */
function get_num_posts($dbh, $uID) {
	$uID = pg_escape_string($dbh, $uID);
	$query = 'SELECT count(*) AS num FROM post WHERE username=\'' . $uID . '\'';
	error_log($query);
	$result = pg_query($dbh, $query);
	$resultArray = pg_fetch_array($result);
	if (!$resultArray){
		return array('status'=>0);
	}
	return array('status'=>1, 'count'=>$resultArray['num']);
}

/*
 * Get the number of hashtags used by user $uID
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'count' => (The number of hashtags)
 * )
 */
function get_num_tags_of_user($dbh, $uID) {
	$uID = pg_escape_string($dbh, $uID);
	$query = 'SELECT count(DISTINCT hashtagname) AS num FROM hashtag, post WHERE hashtag.postid=post.postid AND post.username=\'' . $uID . '\'';
	error_log($query);
	$result = pg_query($dbh, $query);
	$resultArray = pg_fetch_array($result);
	if (!$resultArray){
		return array('status'=>0);
	}
	return array('status'=>1, 'count'=>$resultArray['num']);
}

/*
 * Get the number of votes user $uID made
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'count' => (The number of likes)
 * )
 */
function get_num_votes_of_user($dbh, $uID) {
	$uID = pg_escape_string($dbh, $uID);
	$query = 'SELECT count(*) AS num FROM vote WHERE username=\'' . $uID . '\'';
	error_log($query);
	$result = pg_query($dbh, $query);
	$resultArray = pg_fetch_array($result);
	if (!$resultArray){
		return array('status'=>0);
	}
	return array('status'=>1, 'count'=>$resultArray['num']);
}

/*
 * Get the list of $count users that have posted the most
 * Order by the number of posts (descending), and then by username (A-Z)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'users' => [ (Array of user IDs) ]
 * )
 */
function get_most_active_users($dbh, $count = 10) {
	$query = 'SELECT username, count(postid) AS num FROM post ORDER BY num DESC, username ASC GROUP BY username LIMIT ' . $count;
	error_log($query);
	$result = pg_query($dbh, $query);
	$users = array();
	if ($result){
		while ($resultArray = pg_fetch_array($result)){
  			array_push($users, $resultArray['username']);
		}
		return array('status'=>1, 'users'=>$users);
	}
	return array('status'=>0, 'users'=>$users);
}

/*
 * Get the list of $count posts posted after $from that have the most votes.
 * Order by the number of votes (descending)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'posts' => [ (Array of post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE)
 *		'content' => (CONTENT)
 *		'time' => (UNIXTIME INTEGER)
 *		'coorX' => (coordination x, INTEGER)
 *		'coorY' => (coordination y, INTEGER)
 * )
 */
function get_most_popular_posts($dbh, $count = 10, $from = 0) {
	$query = 'SELECT post.* FROM post, vote WHERE post.postid=vote.postid AND post.ts>' . $from . ' GROUP BY post.postid ORDER BY count(*) DESC LIMIT ' . $count;
	error_log($query);
	$result = pg_query($dbh, $query);
	$posts = array();
	if ($result){
		while ($resultArray = pg_fetch_array($result)){
			$tmp =  array(
	 		'pID' => $resultArray['postid'],
 			'username' => $resultArray['username'],
 			'title' => $resultArray['title'],
        	'content' => $resultArray['body'],
	 		'time' => $resultArray['ts'],
	 		'coorX' => $resultArray['locationx'],
 			'coorY' => $resultArray['locationy'],
  			);
  			array_push($posts, $tmp);
		}
		return array('status'=>1, 'posts'=>$posts);
	}
	return array('status'=>0, 'posts'=>$posts);
}

/*
 * Get the list of $count hashtags that have been used 
 * Order by the number of times being used (descending), and then by tagname (A-Z)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'tags' => [ (Array of tags) ]
 * )
 * Then each tag should have the form
 * array(
 *		'tagname' =>  (tagname)
 *		'occurence' => (number of times that is used)
 * )
 */
function get_most_popular_tags($dbh, $count = 5) {
	$query = 'SELECT hashtagname, count(*) AS num FROM hashtag GROUP BY hashtagname ORDER BY num DESC, hashtagname ASC LIMIT ' . $count;
	error_log($query);
	$result = pg_query($dbh, $query);
	$hashtags = array();
	if ($result){
		while ($resultArray = pg_fetch_array($result)){
			$tmp =  array(
	 		'tagname' => $resultArray['hashtagname'],
 			'occurence' => $resultArray['num']
  			);
  			array_push($hashtags, $tmp);
		}
		return array('status'=>1, 'tags'=>$hashtags);
	}
	return array('status'=>0, 'tags'=>$hashtags);
}

/*
 * Get the list of $count tag pairs that have been used together
 * Avoid duplicate pairs like (#foo #bar) and (#bar #foo). 
 * They should only be counted once with alphabetic order (#bar #foo)
 * Order by the number of times being used (descending)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'tagpairs' => [ (Array of tags) ]
 * )
 * Then each tagpair should have the form
 * array(
 *		'tagname1' => (tagname1)
 *		'tagname2' => (tagbane2)
 *		'occurence' => (number of times that occurs)
 * )
 */
function get_most_popular_tag_pairs($dbh, $count = 5) {
	$query = 'SELECT h1.hashtagname AS tagname1, h2.hashtagname AS tagname2, count(*) AS num FROM hashtag AS h1, hashtag AS h2 WHERE h1.postid = h2.postid AND h1.hashtagname < h2.hashtagname GROUP BY h1.hashtagname, h2.hashtagname ORDER BY num DESC LIMIT ' . $count;
	error_log($query);
	$result = pg_query($dbh, $query);
	$hashtags = array();
	if ($result){
		while ($resultArray = pg_fetch_array($result)){
			#error_log($resultArray['tagname1']);
			#error_log($resultArray['tagname2']);
			#error_log($resultArray['num']);
			$tmp =  array(
	 		'tagname1' => $resultArray['tagname1'],
 			'tagname2' => $resultArray['tagname2'],
 			'occurence' => $resultArray['num']
  			);
  			array_push($hashtags, $tmp);
		}
		return array('status'=>1, 'tagpairs'=>$hashtags);
	}
	return array('status'=>0, 'tagpairs'=>$hashtags);
}

/*
 * Recommend posts for user $user.
 * A post $p is a recommended post for $user if like minded users of $user also voted for the post,
 * where like minded users are users who voted for the posts $user voted for.
 * Result should not include posts $user voted for.
 * Rank the recommended posts by how many like minded users voted for the posts.
 * The set of like minded users should not include $user self.
 *
 * Return associative array of the form:
 * array(
 *    'status' =>   (1 for success and 0 for failure)
 *    'posts' => [ (Array of post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE)
 *		'content' => (CONTENT)
 *		'time' => (UNIXTIME INTEGER)
 *		'coorX' => (coordination x, INTEGER)
 *		'coorY' => (coordination y, INTEGER)
 * )
 */
function get_recommended_posts($dbh, $count = 10, $user) {
	$user = pg_escape_string($dbh, $user);
	#similar user
	$query = 'CREATE VIEW similaruser AS SELECT DISTINCT users.username FROM users, post WHERE users.username=post.username AND post.username != \'' . $user . '\' AND post.postid IN (SELECT postid FROM vote WHERE username = \'' . $user . '\')';
	error_log($query);
	$result = pg_query($dbh, $query);
	if (!$result){
		return array('status'=>0, 'posts'=>array());
	}
	#goodpost
	$query = 'CREATE VIEW goodpost AS SELECT count(vote.username) AS score, postid FROM vote,similaruser WHERE vote.username = similaruser.username GROUP BY postid ORDER BY score DESC';
	error_log($query);
	$result = pg_query($dbh, $query);
	if (!$result){
		return array('status'=>0, 'posts'=>array());
	}
	#select
	$query = 'SELECT * FROM goodpost, post WHERE post.postid=goodpost.postid LIMIT ' . $count;
	error_log($query);
	$result = pg_query($dbh, $query);
	#drop view
	$gpquery = 'DROP VIEW IF EXISTS goodpost;';
	$gpresult = pg_query($dbh, $gpquery);
	#drop view
	$suquery = 'DROP VIEW IF EXISTS similaruser';
	$suresult = pg_query($dbh, $suquery);


	$posts = array();
	if ($result){
		while ($resultArray = pg_fetch_array($result)){
			$tmp =  array(
	 		'pID' => $resultArray['postid'],
 			'username' => $resultArray['username'],
 			'title' => $resultArray['title'],
        	'content' => $resultArray['body'],
	 		'time' => $resultArray['ts'],
	 		'coorX' => $resultArray['locationx'],
 			'coorY' => $resultArray['locationy'],
  			);
  			array_push($posts, $tmp);
		}
		return array('status'=>1, 'posts'=>$posts);
	}
	return array('status'=>0, 'posts'=>$posts);
}

/*
 * Delete all tables in the database and then recreate them (without any data)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 * )
 */
function reset_database($dbh) {
	
	
	$query = 'DROP TABLE IF EXISTS vote, hashtag, post, users CASCADE';
	$result = pg_query($dbh, $query);
	if(!$result){
		return array("status" => 0);
	}

	$query = 'CREATE TABLE IF NOT EXISTS users(username VARCHAR(50) PRIMARY KEY, password VARCHAR(32) NOT NULL)';
	$result = pg_query($dbh, $query);
	if(!$result){
		return array("status" => 0);
	}



	$query = 'CREATE TABLE IF NOT EXISTS post(postid SERIAL PRIMARY KEY, username VARCHAR(50) REFERENCES users(username) ON DELETE CASCADE, title VARCHAR(20) NOT NULL, body VARCHAR(42) NOT NULL, locationx INT NOT NULL, locationy INT NOT NULL, ts BIGINT NOT NULL)';
	$result = pg_query($dbh, $query);
	if(!$result){
		return array("status" => 0);
	}



	$query = 'CREATE TABLE IF NOT EXISTS hashtag(hashid SERIAL PRIMARY KEY,hashtagname VARCHAR(42) NOT NULL,postid INT REFERENCES post(postid) ON DELETE CASCADE)';
	$result = pg_query($dbh, $query);
	if(!$result){
		return array("status" => 0);
	}	

	$query = 'CREATE TABLE vote(username VARCHAR(50) REFERENCES users(username) ON DELETE CASCADE, postid INT REFERENCES post(postid) ON DELETE CASCADE, PRIMARY KEY(username, postid))';
	$result = pg_query($dbh, $query);
	if(!$result){
		return array("status" => 0);
	}

	
	return array("status" => 1);
	

}

?>
