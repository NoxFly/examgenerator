/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

/**
 * @typedef {Object} SubjectOverview
 * @property {string} name
 * @property {string} courseName
 * @property {0|1|2} type
 * @property {number} year
 * @property {number?} coeff
 * @property {string?} dateStart
 * @property {string?} dateEnd
 * @property {number?} startTime
 * @property {number?} endTime
 */

/**
 * @typedef {Object} SubjectChapter
 * @property {number} id
 * @property {string} label
 */

/**
 * @typedef {Object} Question
 * @property {number} id
 * @property {number} chapterId
 * @property {0|1|2} type
 * @property {string} state
 * @property {string[]} proposals
 * @property {number[]?} answers
 * @property {number?} points
 * @property {number?} negPoints
 * @property {number?} appearanceCount
 * @property {number?} appearancePerc
 */

/**
 * @typedef {Object} Answer
 * @property {number} questionId
 * @property {number|null} points
 * @property {string} comment
 * @property {string|number[]} answer
 */

/**
 * @typedef {Object} Student
 * @property {Answer[]} answers
 * @property {number|null} finalMark
 * @property {string} uuid
 * @property {number?} studentId
 */

/**
 * @typedef {Object} Subject
 * @property {SubjectOverview} overview
 * @property {SubjectChapter[]} chapters
 * @property {Question[]} questions
 * @property {Student?} paper
 */


/**
 * @param {Subject} subject
 * @param {boolean} preview
 * @param {boolean} revision
 * @return {HTMLDivElement}
 */
export function renderSubject(subject, preview=true, revision=false) {
    if(typeof subject.overview.type === 'string') {
        subject.overview.type = +subject.overview.type;
    }




    // --- container ---
    const $subject = document.createElement('div');
    $subject.classList.add('subject');

    // ---=== overview ===---
    const $overview = document.createElement('header');
    $overview.classList.add('exam-overview');

    // --- title ---
    const $title = document.createElement('h2');
    $title.classList.add('exam-course-name');
    $title.innerText = subject.overview.courseName;

    // --- informations ---
    // exam name
    const $name = document.createElement('h3');
    $name.classList.add('exam-name');
    $name.innerText = subject.overview.name;

    const $additionalInfos = document.createElement('p');
    $additionalInfos.classList.add('exam-coeff-type');
    $additionalInfos.innerText =  `Coefficient ${subject.overview.coeff} - ${getExamTypeName(subject.overview.type)}`;


    $overview.append($title, $name, $additionalInfos);


    // final mark
    if(!revision && subject.paper && subject.paper.finalMark !== null) {
        const totalPoints = subject.questions.reduce((prev, curr) => prev + curr.points, 0);

        const $mark = document.createElement('div');
        $mark.classList.add('student-mark');
        $mark.innerText = `${subject.paper.finalMark} / ${totalPoints}`;

        $overview.appendChild($mark);
    }



    /** @type {{[chapterId: number]: Question}} */
    const questions = {};

    for(const chapter of subject.chapters) {
        questions[chapter.id] = [];
    }

    for(const question of subject.questions) {
        questions[question.chapterId].push(question);
    }



    // ---=== content ===---
    const $content = document.createElement('article');
    $content.classList.add('exam-content');

    const misc = {
        questionIdx: 1,
        revision
    };

    if(subject.paper) {
        misc.student = subject.paper;

        const $stuNum = document.createElement('span');
        $stuNum.classList.add('student-uuid');
        $stuNum.innerText = subject.paper.uuid?? '';
        $subject.appendChild($stuNum);
    }

    // --- for each chapter ---
    for(const chapter of subject.chapters) {
        const $chapterSection = createChapter(chapter, questions[chapter.id], misc);
        $content.appendChild($chapterSection);
    }

    if(preview) {
        $content.querySelectorAll('.question-answer').forEach($ipt => {
            $ipt.setAttribute('disabled', true);
            $ipt.setAttribute('readonly', true);
        });
    }


    $subject.append($overview, $content);

    return $subject;
}





/**
 * @param {SubjectChapter} chapter
 * @param {Question[]} questions
 * @param {*} misc
 */
function createChapter(chapter, questions, misc) {
    // --- wrapper ---
    const $chapterSection = document.createElement('section');
    $chapterSection.classList.add('exam-chapter-section', `chapter-${chapter.id}`);

    // --- chapter title ---
    const $chapterTitle = document.createElement('h4');
    $chapterTitle.classList.add('exam-chapter-title');
    $chapterTitle.innerText = chapter.label;

    $chapterSection.appendChild($chapterTitle);


    // --- for each question of this chapter ---
    for(const question of questions) {
        if('student' in misc) {
            misc.studentAnswer = misc.student.answers.find(a => a.questionId === question.id);
        }

        const $question = createQuestion(question, misc);
        $chapterSection.appendChild($question);
    }

    return $chapterSection;
}





/**
 * @param {Question] question
 * @param {*} misc
 */
function createQuestion(question, misc) {
    // --- question wrapper ---
    const $question = document.createElement('form');
    $question.classList.add('exam-question', `question-${question.id}`, `question-${question.type===0?'text':'mcq'}`);
    $question.dataset.questionId = question.id;

    // --- question title ---
    const $questionTitle = document.createElement('h5');
    $questionTitle.classList.add('question-title');
    $questionTitle.innerText = `Question ${misc.questionIdx}`;

    // mark
    if(misc.revision) {
        const pt = misc.studentAnswer?.points;

        const $markWrp = document.createElement('div');
        $markWrp.classList.add('mark');

        const $markIpt = document.createElement('input');
        $markIpt.classList.add('ipt-teacher-review', 'ipt-mark');
        $markIpt.type = 'text';
        $markIpt.placeholder = '0';

        if('studentAnswer' in misc) {
            if(pt === null) {
                $markWrp.classList.add('need-completion');
            }
            else {
                $markIpt.value = pt;
            }
        }

        const $maxMark = document.createElement('span');

        $maxMark.innerText = `/ ${question.points}`;

        $markWrp.append($markIpt, $maxMark);

        $markIpt.addEventListener('input', e => {
            const c = e.data;
            let v = $markIpt.value;

            if(isNaN(c) && c !== '.' || v.indexOf('.') !== v.lastIndexOf('.')) {
                v = v.replace(c, '');
            }

            if(v.length === 2 && v[0] === v[1] && v[0] === '0') {
                v = '0';
            }

            if(v.length > 1 && v.startsWith('0')) {
                v = v.substring(1);
            }

            if(+v > question.points) {
                v = question.points;
            }

            if(v.length === 0) {
                $markWrp.classList.add('need-completion', 'changed');
            }
            else {
                $markWrp.classList.remove('need-completion');

                if(+v !== pt) {
                    if(!$markWrp.classList.contains('changed')) {
                        $markWrp.classList.add('changed');
                    }
                }
                else {
                    $markWrp.classList.remove('changed');
                }
            }

            $markIpt.value = v;
        });

        $markIpt.addEventListener('blur', () => {
            if($markIpt.value.endsWith('.0')) {
                $markIpt.value = $markIpt.value.substring(0, $markIpt.value.length-2);
            }
            else if($markIpt.value.endsWith('.')) {
                $markIpt.value = $markIpt.value.substring(0, $markIpt.value.length-1);
            }
            else if($markIpt.value.startsWith('.')) {
                $markIpt.value = '0' + $markIpt.value;
            }
        });

        $questionTitle.appendChild($markWrp);
    }

    // --- question state ---
    const $state = document.createElement('p');
    $state.classList.add('question-state');
    $state.innerText = question.state;

    // --- question points ---
    if('points' in question) {
        const unit = (question.points > 1 || question.negPoints < -1)? 'pts' : 'pt';

        const negPoints = question.negPoints? ` / ${question.negPoints}` : '';
        const mark = (!misc.revision && misc.studentAnswer && misc.studentAnswer.points !== null)
            ? `${misc.studentAnswer.points} / `
            : '';

        const $points = document.createElement('span');
        $points.classList.add('question-points');
        $points.innerText = `(${mark}${question.points}${negPoints} ${unit})`;
        
        $questionTitle.appendChild($points);
    }

    // --- question % of appearance ---
    if('appearancePerc' in question) {
        const $appearancePerc = document.createElement('span');
        $appearancePerc.classList.add('question-appearance-perc');
        $appearancePerc.innerText = `${question.appearancePerc}%`;

        $questionTitle.appendChild($appearancePerc);
    }

    const $proposals = document.createElement('div')
    $proposals.classList.add('question-proposals');

    const iptName = `answer-${question.id}`;
    const iptId = `${iptName}-${misc.questionIdx}`;

    // --- comment ---
    let $commentBtn, $commentIpt, $commentWrp;
    const comment = misc.studentAnswer?.comment || '';

    if(misc.studentAnswer && (misc.revision || comment.length > 0)) {
        $commentWrp = document.createElement('div');
        $commentWrp.classList.add('comment');

        $commentIpt = document.createElement('textarea');
        $commentIpt.placeholder = 'Laissez une note...';
        $commentIpt.value = comment;

        if(misc.revision) {
            $commentWrp.classList.add('hidden');
            $commentIpt.classList.add('ipt-teacher-review', 'ipt-comment');
        }
        else {
            $commentIpt.setAttribute('readonly', true);
        }

        $commentWrp.appendChild($commentIpt);

        $questionTitle.appendChild($commentWrp);
    }

    // comment / note
    if(misc.revision) {
        $commentBtn = document.createElement('button');
        $commentBtn.className = 'btn-comment text';

        if(comment.length > 0) {
            $commentBtn.classList.add('filled');
        }

        $commentBtn.addEventListener('click', e => {
            e.preventDefault();

            $commentWrp.classList.toggle('hidden');

            if(!$commentWrp.classList.contains('hidden')) {
                $commentIpt.focus();
            }
        });

        $commentIpt.addEventListener('input', () => {
            const v = $commentIpt.value.trim();

            if(v !== comment) {
                if(!$commentWrp.classList.contains('changed')) {
                    $commentWrp.classList.add('changed');
                }
            }
            else {
                $commentWrp.classList.remove('changed');
            }

            if(v.length > 0) {
                $commentBtn.classList.add('filled');
            }
            else {
                $commentBtn.classList.remove('filled');
            }
        });

        $questionTitle.appendChild($commentBtn);

    }

        
    switch(question.type) {
        // text
        case 0:
            createTextAnswer(question, iptName, iptId, $proposals, misc);
            break;

        // MCQ unique
        case 1:
            createUniqueAnswer(question, iptName, iptId, $proposals, misc);
            break;

        // MCQ multiple
        case 2:
            createMultipleAnswer(question, iptName, iptId, $proposals, misc)
            break;
    }

    misc.questionIdx++;

    $question.append($questionTitle, $state, $proposals);

    return $question;
}

/**
 * 
 * @param {Question} question 
 * @param {string} iptName
 * @param {string} iptId
 * @param {HTMLElement} $proposals
 */
function createTextAnswer(question, iptName, iptId, $proposals, misc) {
    $proposals.classList.add('prop-text');

    if('studentAnswer' in misc) {
        const $iptT = document.createElement('div');
        $iptT.classList.add('question-answer');

        $iptT.id = iptId;

        if('answers' in question) {
            $iptT.innerText = question.answers;
        }

        const $ans = document.createElement('div');
        $ans.classList.add('student-answer');
        $ans.innerText = misc.studentAnswer.answer;

        const $tabs = document.createElement('div');
        $tabs.classList.add('answer-tabs');

        const $inner = document.createElement('div');
        $inner.classList.add('tab-content');

        const $nav = document.createElement('ul');
        const tabs = [
            ['Réponse de l\'étudiant', $ans],
            ['Réponse attendue', $iptT],
        ];

        for(const i in tabs) {
            const tab = tabs[i];
            const $li = document.createElement('li');

            $li.innerText = tab[0];
            $li.dataset.tab = i;

            $nav.appendChild($li);

            const $tabContent = document.createElement('div');
            $tabContent.classList.add('tab-content');

            if(i == 0) {
                $li.classList.add('selected');
                $tabContent.classList.add('selected');
            }

            $tabContent.appendChild(tab[1]);

            $tabs.appendChild($tabContent);

            $li.addEventListener('click', () => {
                if($li.classList.contains('selected'))
                    return;

                $nav.querySelector('.selected')?.classList.remove('selected');
                $tabs.querySelector('.selected')?.classList.remove('selected');

                $li.classList.add('selected');
                $tabContent.classList.add('selected');
            });
        }

        $proposals.append($nav, $tabs);
    }
    else {
        const $iptT = document.createElement('textarea');
        $iptT.classList.add('question-answer');

        $iptT.setAttribute('name', iptName);

        $iptT.id = iptId;

        if('answers' in question) {
            $iptT.value = question.answers;
        }

        $proposals.appendChild($iptT);
    }
}

/**
 * 
 * @param {Question} question 
 * @param {string} iptName 
 * @param {string} iptId 
 * @param {HTMLElement} $proposals 
 */
function createUniqueAnswer(question, iptName, iptId, $proposals, misc) {
    createMcqAnswer(
        question.proposals,
        (('answers' in question) && question.answers !== null)? [question.answers] : null,
        'radio',
        iptName,
        iptId,
        $proposals,
        misc
    );
}

/**
 * 
 * @param {Question} question 
 * @param {string} iptName 
 * @param {string} iptId 
 * @param {HTMLElement} $proposals 
 */
function createMultipleAnswer(question, iptName, iptId, $proposals, misc) {
    createMcqAnswer(
        question.proposals,
        question.answers? question.answers : null,
        'checkbox',
        iptName,
        iptId,
        $proposals,
        misc
    );
}

/**
 * 
 * @param {string[]} proposals 
 * @param {string[]|null} answers 
 * @param {'radio'|'checkbox'} type 
 * @param {string} iptName 
 * @param {string} iptId 
 * @param {HTMLElement} $proposals 
 */
function createMcqAnswer(proposals, answers, type, iptName, iptId, $proposals, misc) {
    let iptIdx = 0;


    if(answers !== null) {
        $proposals.classList.add('preview');
    }

    $proposals.classList.add('mcq-type-' + type);

    for(const prop of proposals) {
        const id = `${iptId}-${++iptIdx}`;
        const $iptMCQ = document.createElement('input');
        const $labelMCQ = document.createElement('label');

        $iptMCQ.setAttribute('type', type);
        $iptMCQ.classList.add('question-answer');

        if(!misc.revision)
            $iptMCQ.setAttribute('name', iptName);

        $iptMCQ.id = id;

        $labelMCQ.innerText = prop;
        $labelMCQ.setAttribute('for', id);
        $labelMCQ.prepend($iptMCQ);

        $proposals.classList.add('prop-mcq');

        $proposals.appendChild($labelMCQ);
    }

    if('studentAnswer' in misc) {
        const stuAns = (typeof misc.studentAnswer.answer === 'object')? misc.studentAnswer.answer : [misc.studentAnswer.answer];

        for(const ans of stuAns) {
            let iptClass = '';

            const i = answers.indexOf(ans);

            if(i > -1) {
                iptClass = 'correct';

                answers.splice(i, 1);
            }
            else {
                iptClass = 'incorrect';
            }

            const $prop = $proposals.children[ans].children[0];

            $prop.checked = true;
            $prop.classList.add(iptClass);
        }

        for(const ans of answers) {
            const $prop = $proposals.children[ans].children[0];
            $prop.checked = true;
            $prop.classList.add('should-be');
        }
    }

    else if(answers !== null) {
        for(const ansIdx of answers) {
            $proposals.children[ansIdx].children[0].checked = true;
        }
    }
}


/**
 * 
 * @param {SubjectOverview.type} type 
 */
function getExamTypeName(type) {
    switch(type) {
        case 0:
            return 'CC';
        case 1:
            return 'CI';
        case 2:
            return 'CF';
        default:
            return '';
    }
}