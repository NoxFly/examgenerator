/**
 * @copyright Copyrights (C) 2023 Jean-Charles Armbruster All rights reserved.
 * @author Jean-Charles Armbruster
 * @since 2023
 * @package uha.archi_web
 */

import { GET } from '../../ajax';
import { createToast, page } from '../../script';


const examId = +page.url.substring(page.url.lastIndexOf('/')+1);

let data;
let average;

async function loadSubjects() {
    try
    {
        data = await GET(`/api/exams/byId/${examId}/results`);

        //console.log(data);

        const totalPoints = data.questions.reduce((prev, curr) => prev + curr.points, 0);
        average = {
            exam : 0, 
            question : {}, 
            nbStudentAbove : 0, 
            nbStudentAnswered : Object.keys(data.answers).length
        };

        for (let question of data.questions)
        {
            average.question[question.id] = {nbAnswer : 0, averagePoint : 0};
        }

        for (let student in data.answers)
        {   
            student = data.answers[student];
            average.exam += student.finalMark;
            if (student.finalMark >= totalPoints/2)
            {
                average.nbStudentAbove++;
            }
            for (let answer of student.answers)
            {
                average.question[answer.questionId].nbAnswer++;
                average.question[answer.questionId].averagePoint += answer.points;
            }
        }
        

        for (let answer in average.question)
        {
            answer = average.question[answer];
            answer.averagePoint /= answer.nbAnswer;
        }
        average.exam /= average.nbStudentAnswered
        //console.log(average);

    }
    catch(e) {
		console.error(e);
		createToast('Une erreur est survenue lors de la récupération des réponses', false, 2000);
	}
}

async function display()
{
    const data = {
        labels: [
          'Au dessus de la moyenne',
          'En dessous de la moyenne'
        ],
        datasets: [{
          label: 'Eleve',
          data: [average.nbStudentAbove, average.nbStudentAnswered-average.nbStudentAbove],
          backgroundColor: [
            'rgb(255, 99, 132)',
            'rgb(54, 162, 235)'
          ]
        }],
        options: {
          responsive: true
      }
      };

      const config = {
        type: 'pie',
        data: data,
      };
      const ctx=document.getElementById('chart');
      new Chart(ctx, config);
}

if(!isNaN(examId)) {
	await loadSubjects();
    await display();
}
//chaque question tableau de chaque réponse
//% de gens moyenne
//% de réponses juste par question
//chartjs