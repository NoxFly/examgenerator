/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

import { common, nextStepEvent } from "../common";
import { renderSubject } from "../renderSubject";

let $table, $confirmBtn, $form, $iptTotal;
let marks = {};

export function load() {
    const $wrapper = document.getElementById('subject-wrapper');
    const $subject = renderSubject(common.subject, true);

    $table = document.getElementById('marks-table');
    $form = $table?.querySelector('form');
    $confirmBtn = $table?.querySelector('.btn-confirm');
    $iptTotal = $table?.querySelector('#totalPoints');

    $confirmBtn.addEventListener('click', confirmMarks);
    $iptTotal.addEventListener('input', updateMarksRatio);
    
    if($form) {
        buildTable();
    }

    if('marks' in common) {
        for(const question of common.marks) {
            marks[question.id].el.value = question.points;
            marks[question.id].pt = question.points;
        }

        $iptTotal.value = getTotalPoints();

        updateConfirmBtn();
    }

    // DEV
    // else {
    //     for(const qid in marks) {
    //         const m = marks[qid];
    //         m.el.value = 1;
    //         m.pt = 1;
    //     }

    //     updateConfirmBtn();
    //     $confirmBtn?.click();
    // }

    $wrapper.appendChild($subject);
}

export function unload() {
    $confirmBtn.removeEventListener('click', confirmMarks);
    $iptTotal.removeEventListener('input', updateMarksRatio);

    for(const qid in marks) {
        marks[qid].el.removeEventListener('input', updateMark);
    }
}

function confirmMarks() {
    common.marks = Object.entries(marks).map(m => ({ id: +m[0], points: m[1].pt }));

    document.dispatchEvent(nextStepEvent);
}

function buildTable() {
    for(let i in common.subject.questions) {
        const $q = document.createElement('label');

        const qid = common.subject.questions[i].id;

        $q.classList.add('question-mark');
        $q.innerHTML = `Q<span>${i}</span>`;

        const $ipt = document.createElement('input');
        $ipt.setAttribute('type', 'text');
        $ipt.setAttribute('placeholder', '0');
        
        $ipt.dataset.qid = qid;
        
        marks[qid] = {
            el: $ipt,
            pt: 0
        };

        $ipt.addEventListener('input', updateMark);

        $q.appendChild($ipt);

        $form.appendChild($q);
    }
}

function updateMark() {
    this.value = this.value.replace(/[^\d]/, '');
 
    const value = +this.value;
    const id = +this.dataset.qid;

    $iptTotal.value = +$iptTotal.value + (value - marks[id].pt);

    marks[id].pt = value;

    updateConfirmBtn();
}

function updateMarksRatio() {
    this.value = this.value.replace(/[^\d]/, '');

    const value = +this.value;
    
    if(value === 0) {
        return;
    }

    const prevTotal = getTotalPoints() || 1; // avoid division by 0

    for(const qid in marks) {
        const m = marks[qid];

        if(m.el.value.length === 0 || m.pt === 0) {
            continue;
        }

        const prevPerc = m.pt * 100 / prevTotal;
        const nextVal = Math.round(prevPerc * value) / 100;
        const intPart = Math.trunc(nextVal);
        const decPart = Math.round((nextVal - intPart) * 100) / 100;
        const nextPt = intPart + decPart;

        m.pt = nextPt;
        m.el.value = m.pt;
    }
}

function updateConfirmBtn() {
    for(const qid in marks) {
        if(marks[qid].pt === 0) {
            $confirmBtn.setAttribute('disabled', true);
            return;
        }
    }

    $confirmBtn.removeAttribute('disabled');
}

function getTotalPoints() {
    return Object.values(marks).map(m => m.pt).reduce((a, b) => a + b, 0);
}