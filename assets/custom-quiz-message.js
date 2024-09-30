document.addEventListener("DOMContentLoaded", function() {
    // Selecciona el botón "Start Quiz"
    var startQuizButton = document.querySelector('.wpProQuiz_button[name="startQuiz"]');

    if (startQuizButton && typeof quizData !== 'undefined') {
        // Crea el contenedor del mensaje
        var messageDiv = document.createElement('div');
        messageDiv.className = 'custom-quiz-message';
        messageDiv.id = 'quiz-start-message';  // Asignar ID al contenedor del mensaje
        messageDiv.innerHTML = `
            <a id="back-to-course-link" href="${document.referrer}" class="back-to-course-link">Volver al curso</a>
            <p id="quiz-start-paragraph">Estás a punto de iniciar la evaluación del curso <strong>${quizData.courseName}</strong>, que consta de 30 preguntas. Esta evaluación es contrarreloj, tendrás 45 segundos para responder cada pregunta. Al finalizar, te entregaremos tu resultado, y podrás decidir si quieres publicarlo en el ranking público o mantenerlo privado. Recuerda que solo puedes rendir esta evaluación 3 veces, por lo que te sugerimos prestar mucha atención y responder conscientemente. Cuando estés listo, haz clic en <strong>Comenzar</strong>. ¡Éxito!</p>
        `;

        // Inserta el mensaje antes del botón "Start Quiz"
        startQuizButton.parentNode.insertBefore(messageDiv, startQuizButton);
    }
});
