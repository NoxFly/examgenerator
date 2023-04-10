<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */


defined('_NOX') or die('401 Unauthorized');


// php 7.x does not have unions (for bitwise op)
// dealing with this
abstract class UserType {
	const NONE = 0;
	const ANONYMOUS = 1;
	const STUDENT = 2;
	const TEACHER = 4;
	const ADMIN = 8;
}

abstract class ExamType {
	const CC = '0';
	const CI = '1';
	const CF = '2';
}

abstract class ExamState {
	const COMING = '0';
	const PENDING = '1';
	const DONE = '2';
	const REVISED = '3';
}

abstract class QuestionType {
	const TEXT = '0';
	const UNIQUE = '1';
	const MULTIPLE = '2';
}


/**
 * Returns either a contains b or not.
 * @param int $a
 * @param int $b
 * @return bool
 */
function bitwiseAND($a, $b) {
	return ($a & $b) === $b;
}


/**
 * @param int $length
 * @param bool $includeSpecial
 * @param bool $includeDigit
 * @param bool $includeUpper
 * @param bool $includeLower
 * @return string
 */
function generateRandomString(
	$length,
	$includeSpecial=false,
	$includeDigit=true,
	$includeUpper=true,
	$includeLower=true
) {
	$characters = '';

	if($includeLower) {
		$characters .= 'abcdefghijklmnopqrstuvwxyz';
	}

	if($includeUpper) {
		$characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	}

	if($includeDigit) {
		$characters .= '0123456789';
	}

	if($includeSpecial) {
		$characters .= '()[]{}-+/*|&~#_^@°=$£µ%.?,;:!§<>';
	}

	$charactersLength = mb_strlen($characters) - 1;
	$randomString = '';

	str_shuffle($characters);

	for ($i = 0; $i < $length; $i++) {
		$r = rand(0, $charactersLength);
		$randomString .= mb_substr($characters, $r, 1);
	}

	return $randomString;
}