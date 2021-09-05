<?php

/*
 *  Configure these bits
 */ 
$user = "your email address";
$pass = "your password";
$downloadDir = "./downloads/";

if (!file_exists($downloadDir)) mkdir($downloadDir);
$frameworkDir = $downloadDir."frameworks/";
if (!file_exists($frameworkDir)) mkdir($frameworkDir);

$loginResult = apiRequest('auth/login', ['email'=>$user, 'password'=>$pass])[0];
if (!$loginResult->id) die("Failed to login:\n".print_r($loginResult));

$session = apiRequest('auth/create-session', ['id'=>$loginResult->id, 'password'=>$pass], ['x-api-product: iConnect']);
if (!$session->key) die("Failed to create session:\n".print_r($session,1));

$cursor = false;
do {
	print "Downloading a page of posts".($cursor?' with cursor '.$cursor:'')."\n";
	$posts = apiRequest('posts'.($cursor?'?cursor='.$cursor:''), false, ['x-api-key: '.$session->key, 'x-client-version: 3.16.0']);
	foreach($posts->posts as $post) {
		$child = $post->child->forename.' '.$post->child->surname;
		print " - Post ".$post->id." for ".$child." dated ".$post->startTime."\n";
		if (!file_exists($downloadDir.$child)) mkdir($downloadDir.$child);
		$postDir = $downloadDir.$child."/".$post->id;
		if (!file_exists($postDir)) mkdir($postDir);
		file_put_contents($postDir."/postdata.json", json_encode($post));
		if (isset($post->media) && is_array($post->media)) {
			foreach($post->media as $media) {
				$filename = $postDir."/".$media->fileName;
				if (file_exists($filename)) {
					//print "    - Already got ".$media->type." ".$media->fileName."\n";				
				} else {
					print "    - Downloading ".$media->type." ".$media->fileName."\n";				
					downloadFile('https://api.parentzone.me/v1/media/'.$media->id.'/full?key='.$session->key.'&u='.$media->updated, $filename);
				}
			}
		}
		if (isset($post->gradedCount)) {
			$filename = $postDir."/gradings.json";
			if (!file_exists($filename)) {
				print "    - Downloading gradings\n";
				$gradingData = apiRequest('posts/'.$post->id.'/gradings', false,  ['x-api-key: '.$session->key, 'x-client-version: 3.16.0']);
				foreach($gradingData->gradings as $grading) {
					$frameworkFile = $frameworkDir."framework".$grading->frameworkID;
					if (!file_exists($frameworkFile)) {
						print "       - Downloading framework ".$grading->frameworkID."\n";
						$frameworkData = apiRequest('config/frameworks/'.$grading->frameworkID, false,  ['x-api-key: '.$session->key, 'x-client-version: 3.16.0']);
						file_put_contents($frameworkFile, json_encode($frameworkData));
					}
				}
				file_put_contents($filename, json_encode($gradingData));
			}
		}
	}
	if (isset($posts->cursor)) $cursor = $posts->cursor;

} while(count($posts->posts)>0 && isset($posts->cursor));

function apiRequest($uri, $postData, $headers=[]) {
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.parentzone.me/v1/".$uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if ($postData) {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
		$headers[] = 'Content-Type: application/json; charset=utf-8';
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	$result = curl_exec($ch);
	curl_close($ch);
	
	return json_decode($result);
}

function downloadFile($url, $filename) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);
	file_put_contents($filename, $result);
	curl_close($ch);
}
