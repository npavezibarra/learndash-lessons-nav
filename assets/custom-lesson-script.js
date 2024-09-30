document.addEventListener("DOMContentLoaded", function() {
    // Find the entry-content div
    var entryContentDiv = document.querySelector('main .entry-content');

    if (entryContentDiv && typeof lessonData !== 'undefined') {
        // Create the new div element
        var newDiv = document.createElement('div');
        newDiv.className = 'custom-left-div';
        newDiv.style.backgroundColor = 'rgb(249, 249, 249)';
        newDiv.style.width = '350px';
        newDiv.style.height = 'auto';
        newDiv.style.float = 'left';
        newDiv.style.marginRight = '20px';
        newDiv.style.marginTop = '0px';
        
        // Add the course outline from the localized variable
        newDiv.innerHTML = `
            <div class="dropdown-header" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin: 0;">Contenido del curso</h4>
                <img src="` + lessonData.arrowImageUrl + `" class="dropdown-arrow" style="width: 16px; height: 16px; transform: rotate(0deg); transition: transform 0.3s ease-in-out;">
            </div>
            <div class="dropdown-content course-outline" style="display: block; max-height: 500px; overflow-y: auto;">` + lessonData.lessonList + `</div>`;

        // Insert the new div before the entry-content div
        entryContentDiv.parentNode.insertBefore(newDiv, entryContentDiv);

        function toggleDropdown() {
            const dropdownHeader = newDiv.querySelector('.dropdown-header');
            const dropdownContent = newDiv.querySelector('.dropdown-content');
            const dropdownArrow = newDiv.querySelector('.dropdown-arrow');

            if (window.innerWidth < 970) {
                dropdownContent.style.display = "none"; 
                dropdownArrow.style.transform = "rotate(0deg)";  

                dropdownHeader.addEventListener('click', function() {
                    if (dropdownContent.style.display === "none") {
                        dropdownContent.style.display = "block";
                        dropdownArrow.style.transform = "rotate(180deg)"; 
                    } else {
                        dropdownContent.style.display = "none";
                        dropdownArrow.style.transform = "rotate(0deg)";  
                    }
                });
            } else {
                dropdownContent.style.display = "block";
                dropdownArrow.style.display = "none";
            }
        }

        toggleDropdown();
        window.addEventListener('resize', toggleDropdown);

        // Scroll the current lesson into view inside the course-outline div with smooth scrolling
        var currentLesson = document.querySelector('.current-lesson');
        var courseOutline = newDiv.querySelector('.course-outline');

        if (currentLesson && courseOutline) {
            // Get the position of the current lesson inside the course-outline
            const lessonPosition = currentLesson.offsetTop;
            const courseOutlineHeight = courseOutline.clientHeight;

            // Check if the lesson is not already visible inside the scrollable container
            if (lessonPosition > courseOutline.scrollTop + courseOutlineHeight || lessonPosition < courseOutline.scrollTop) {
                courseOutline.scroll({
                    top: lessonPosition - (courseOutlineHeight / 2), // Center the lesson in view
                    behavior: 'smooth'
                });
            }
        }
    }
});
