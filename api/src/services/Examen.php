<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


/**
 * A little class that serves to use uniform question object all through the project.
 */
class Question {
    /**
     * @var int $id
     * @var int $chapterId
     * @var int $type
     * @var string $state
     * @var string|string[] $proposals
     * @var string|string[] $answers
     * */
    public $id, $chapterId, $type, $state, $proposals, $answers;
    /** @var int */
    public $createdAt;
    /**
     * @var int $points
     * @var int $negPoints
     */
    public $points, $negPoints;
    /**
     * @var int $appearanceCount
     * @var int $appearancePerc
     */
    public $appearanceCount, $appearancePerc;

    function __construct(
        $id,
        $chapterId,
        $type,
        $state,
        $proposals,
        $answers,
        $createdAt,
        $points = NULL,
        $negPoints = NULL,
        $appearanceCount = NULL,
        $appearancePerc = NULL
    ) {
        $this->id = $id;
        $this->chapterId = $chapterId;
        $this->type = $type;
        $this->state = $state;
        $this->proposals = $proposals;
        $this->answers = $answers;
        $this->createdAt = $createdAt;
        $this->points = $points;
        $this->negPoints = $negPoints;
        $this->appearanceCount = $appearanceCount;
        $this->appearancePerc = $appearancePerc;

        if($this->points === NULL) {
            unset($this->points);
        }

        if($this->negPoints === NULL) {
            unset($this->negPoints);
        }

        if($this->appearanceCount === NULL) {
            unset($this->appearanceCount);
        }

        if($this->appearancePerc === NULL) {
            unset($this->appearancePerc);
        }
    }
}



class ExamenService {
    /**
     * @param Database $db
     * @param stdClass $user
     * @param int $courseId
     * @return Question[]
     */
    public static function getQuestionsFromCourse($db, $user, $courseId) {
        return ExamenService::getQuestionQueryModel(
            $db, $user,
            '',
            'INNER JOIN coursechapter CC ON (CC.id = Q.id_chapter)',
            'CC.id_course = :courseId',
            ['courseId' => $courseId]
        );
    }

    /**
     * @param Database $db
     * @param stdClass $user
     * @param int $courseId
     * @param int $chapterId
     * @return Question[]
     */
    public static function getQuestionsFromChapter($db, $user, $courseId, $chapterId) {
        return ExamenService::getQuestionQueryModel(
            $db, $user,
            '',
            'INNER JOIN coursechapter CC ON (CC.id = Q.id_chapter)',
            'CC.id_course = :courseId AND Q.id_chapter = :chapterId',
            [
                'courseId' => $courseId,
                'chapterId' => $chapterId
            ]
        );
    }

    /**
     * @param Database $db
     * @param stdClass $user
     * @param int $examId
     * @param bool $showAnswer
     * @return Question[]
     */
    public static function getQuestionsFromExam($db, $user, $examId, $showAnswer=false) {
        return ExamenService::getQuestionQueryModel(
            $db, $user,
            ', EQ.nb_points as nbPoints, EQ.neg_points as negPoints',
            'LEFT JOIN examquestion EQ ON (EQ.id_question = Q.id)',
            'EQ.id_exam = :examId',
            ['examId' => $examId],
            $showAnswer
        );
    }

    /**
     * @param Database $db
     * @param stdClass $user
     * @param string $selectClause
     * @param string $fromClause
     * @param string $whereClause
     * @param array $params
     * @return Question[]
     */
    protected static function getQuestionQueryModel($db, $user, $selectClause, $fromClause, $whereClause, $params, $showAnswer=false) {
        $f = ($showAnswer || bitwiseAND($user->privileges, UserType::TEACHER))? ', Q.answers' : '';
        
        $data = $db->query(
            "SELECT Q.id, Q.id_chapter as chapterId, Q.type, Q.state, Q.proposals, Q.created_at as createdAt $f $selectClause
            FROM question Q
                $fromClause
            WHERE $whereClause",
            $params
        )->fetchAll(PDO::FETCH_OBJ);

        return $data? ExamenService::formatAllQuestions($data) : [];
    }

    /**
     * The param question must at least contain these fields :
     * - type
     * - proposals
     * - [answers] for (neg-)multiple type
     * - [negPoints] for non negative type
     * @param stdClass $question
     * @return Question
     */
    protected static function formatQuestion($question) {
        switch($question->type) {
            case QuestionType::UNIQUE:
                if(gettype($question->proposals) === 'string') {
                    $question->proposals = explode(';', $question->proposals);
                }

                if(isset($question->answers) && gettype($question->answers) === 'string') {
                    $question->answers = intval($question->answers);
                }
                break;

            case QuestionType::MULTIPLE:
                if(gettype($question->proposals) === 'string') {
                    $question->proposals = explode(';', $question->proposals);
                }

                if(isset($question->answers) && gettype($question->answers) === 'string' && $question->answers !== '') {
                    $q = explode(';', $question->answers);
                    $question->answers = array_map(function($e) { return intval($e); }, $q);
                }
                break;
        }

        $question = new Question(
            $question->id,
            $question->chapterId,
            intval($question->type),
            $question->state,
            $question->proposals,
            $question->answers?? NULL,
            $question->createdAt,
            $question->nbPoints?? NULL,
            ($question->type === QuestionType::UNIQUE || $question->type === QuestionType::MULTIPLE)
                ? $question->negPoints?? NULL
                : NULL,
            $question->appearanceCount?? NULL,
            $question->appearancePerc?? NULL
        );

        return $question;
    }

    /**
     * @param stdClass[] $questions
     */
    protected static function formatAllQuestions($questions) {
        foreach($questions as $i => $question) {
            $questions[$i] = ExamenService::formatQuestion($question);
        }

        return $questions;
    }

    /**
     * Give a mark for the answer of a student at a given question.
     * Returns this mark is NULL, it means that the teacher must correct himself this answer.
     * @param Question $model The question model that also contains the answer
     * @param int[]|string $answer The answer of the student to give a mark
     * @return float The attributed noted to this question
     */
    public static function markQuestion($model, $answer) {
        switch($model->type) {
            case QuestionType::TEXT:
                if(mb_strlen($answer) > 512) {
                    throw new ApiException(400, 'Answer has too much characters');
                }

                return NULL;

            case QuestionType::MULTIPLE:
                $isOk = false;
                $nbTrue = 0;

                $exPropCount = count($model->proposals);
                $ansCount = count($answer);

                if($ansCount > $exPropCount) {
                    throw new ApiException(400, 'Wrong answer count');
                }

                if($ansCount === 0) {
                    $isOk = true;
                }
                else {
                    foreach($answer as $ans) {
                        // réponse acceptable
                        if($ans >= 0 && $ans < $exPropCount) {
                            $isOk = true;
                        }

                        if(!$isOk) {
                            throw new ApiException(400, 'Answer not acceptable (multiple 1)');
                        }

                        // réponse juste
                        foreach($model->answers as $i => $exAns) {  
                            if($ans == $exAns) {
                                $nbTrue++;
                                unset($model->answers[$i]);
                                break;
                            }
                        }
                    }
                }

                if(!$isOk) {
                    throw new ApiException(400, 'Answer not acceptable (multiple 2)');
                }

                return $model->points * ($nbTrue / $ansCount); // TODO : negative points ?

            case QuestionType::UNIQUE:
                $isOk = false;
                $exPropCount = count($model->proposals);

                if($answer == $model->answers) {
                    return $model->points;
                }
                
                if($answer >= 0 && $answer < $exPropCount) {
                    $isOk = true;
                }

                if(!$isOk) {
                    throw new ApiException(400, 'Answer not acceptable (single 1)');
                }

                return $model->negPoints?? 0;

            default:
                throw new ApiException(400, 'Unknown question type');
        }
    }


    public static function generateSubject($db, $count, $courseId, $chapters, $questionCount, $mcqPerc) {
        $questions = $db->query(
			"WITH chapters AS (
                SELECT id
                FROM coursechapter
                WHERE id_course = :courseId
                    AND position IN ($chapters)
            )
            SELECT id, id_chapter as chapterId, state, proposals, answers, type, created_at as createdAt
			FROM question
			WHERE id_chapter IN (SELECT id FROM chapters)
            ORDER BY id",
            [
                'courseId' => $courseId
            ]
		)->fetchAll(PDO::FETCH_OBJ);

        $totalQuestionCount = count($questions);

		if($totalQuestionCount < $questionCount) {
			throw new ApiException(400, 'Not enough questions in the database to satisfy the request');
		}


        // recover past apparition percentages

        $totalExams = $db->query(
            'SELECT COUNT(id)
            FROM exam
            WHERE id_course = :courseId',
            [
                'courseId' => $courseId
            ]
        )->fetchColumn();

        $history = $db->query(
            'SELECT EQ.id_question as id, COUNT(EQ.id_question) as count
            FROM examquestion EQ
            LEFT JOIN exam E ON (E.id = EQ.id_exam)
            WHERE E.id_course = :courseId
            GROUP BY EQ.id_question
            ORDER BY EQ.id_question',
            [
                'courseId' => $courseId
            ]
        )->fetchAll(PDO::FETCH_OBJ);

        foreach($questions as $i => $q) {
            $questions[$i]->appearanceCount = 0;
            $questions[$i]->appearancePerc = 0;
        }

        $lastIndex = 0;

        foreach($history as $qHist) {
            $saveIndex = $lastIndex;

            while($lastIndex < $totalQuestionCount && $questions[$lastIndex]->id != $qHist->id) {
                $lastIndex++;
            }

            if($lastIndex >= $totalQuestionCount) {
                $lastIndex = $saveIndex;
                continue;
            }

            $questions[$lastIndex]->appearanceCount = $qHist->count;
            $questions[$lastIndex]->appearancePerc = $qHist->count * $totalExams / 100;

            $lastIndex++;
        }


		// $questions[0] = (NEG-)[UNIQUE/MULTIPLE]
		// $questions[1] = TEXT
		$questions = array_partition($questions, function($question) {
			return $question->type === QuestionType::TEXT;
		});

		$txtTotalCount = count($questions[1]);
		$mcqTotalCount = count($questions[0]);

		
		$qCountMCQ = floor($mcqPerc * $questionCount / 100);
		$qCountTXT = $questionCount - $qCountMCQ;

		// care of limits
		// for MCQ
		if($mcqPerc > 0 && $mcqTotalCount < $qCountMCQ) {
			// wanted to have 100% of mcq questions :
			// then don't generate
			if($mcqPerc === 100) {
				throw new ApiException(400, 'Not enough MCQ questions compared to the given percentage');
			}

			$qCountTXT += min($qCountMCQ - $mcqTotalCount, $txtTotalCount);
			$qCountMCQ = $mcqTotalCount;
		}
		// for TXT
		if($mcqPerc < 100 && $txtTotalCount < $qCountTXT) {
			// wanted to have 100% of text questions :
			// then don't generate
			if($mcqPerc === 0) {
				throw new ApiException(400, 'Not enough TEXT questions compared to the given percentage');
			}

			$qCountMCQ += min($qCountTXT - $txtTotalCount, $mcqTotalCount);
			$qCountTXT = $txtTotalCount;
		}


		// subject selection
		$subjects = [];

		for($i=0; $i < $count; $i++) {
			shuffle($questions[0]);
			shuffle($questions[1]);

			$subQuestions = [];

			if($qCountMCQ > 0) {
				for($j=0; $j < $qCountMCQ; $j++) {
					$subQuestions[] = ExamenService::formatQuestion($questions[0][$j]);
				}
			}

			if($qCountTXT > 0) {
				for($j=0; $j < $qCountTXT; $j++) {
					$subQuestions[] = ExamenService::formatQuestion($questions[1][$j]);
				}
			}

			$subjects[] = $subQuestions;
		}

		return $subjects;
    }
}



function array_partition($array, $callback) {
    $partitions = [[],[]];

    foreach($array as $item) {
        $partitions[$callback($item)][] = $item;
    }

    return $partitions;
}