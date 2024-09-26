<?php
/*
Plugin Name: Learndash Lesson Navigation Menu
Description: Adds a new div before the entry content, applies responsive styles, and adds a dropdown menu effect for lessons, section headers, and quizzes with completion circles.
Version: 2.7
Author: Nicolas Pavez
*/

// Hook into 'wp_footer' to dynamically add the new div before entry-content

// Hook into 'wp_enqueue_scripts' to add custom CSS and JS
add_action('wp_enqueue_scripts', 'enqueue_quiz_resources');

function enqueue_quiz_resources() {
    // Define the plugin directory URL to get the path to the assets folder
    $plugin_url = plugin_dir_url(__FILE__);

    // Enqueue the CSS file from the assets folder
    wp_enqueue_style( 'quiz-result-style', $plugin_url . 'assets/quiz-result.css', array(), '1.0.0', 'all' );

    // Check if the current page is a quiz or lesson page
    if ( is_singular(array('sfwd-quiz', 'sfwd-lessons')) ) {
        // Enqueue the custom JavaScript for the quiz message
        wp_enqueue_script( 'custom-quiz-message', $plugin_url . 'assets/custom-quiz-message.js', array(), '1.0.0', true );

        // If it's a quiz page, get the course name and pass it to the script
        if ( is_singular('sfwd-quiz') ) {
            $course_id = learndash_get_course_id();
            $course_title = get_the_title($course_id);
            wp_localize_script( 'custom-quiz-message', 'quizData', array(
                'courseName' => $course_title
            ));
        }
    }
}

// Hook into 'wp_footer' to dynamically add the new div before entry-content

add_action('wp_footer', 'insert_div_before_entry_content');

function insert_div_before_entry_content() {
    // Ensure this only applies to LearnDash lesson pages (sfwd-lessons)
    if (is_singular('sfwd-lessons')) {  // Only show on lesson pages
        // Get the current course ID based on the current lesson
        $course_id = learndash_get_course_id();
        $current_lesson_id = get_the_ID(); // Get the current lesson ID
        $user_id = get_current_user_id(); // Get the current user ID
        
        // If a valid course ID is found, proceed
        if ($course_id) {
            // Get lessons by menu_order, filtering by course
            $lessons_query = new WP_Query(array(
                'post_type' => 'sfwd-lessons',
                'meta_key' => 'course_id',
                'meta_value' => $course_id,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'posts_per_page' => -1,
            ));

            // Get the section headers from postmeta
            $course_builder_meta = get_post_meta($course_id, 'course_sections', true);
            $section_headers = json_decode($course_builder_meta, true); // Parse the JSON data

            // Prepare the HTML output
            $output = '<div class="course-outline">';
            $output .= '<ul style="list-style-type: none; padding-left: 0;">';

            // Initialize lesson index tracking
            $lessons = $lessons_query->posts;
            $lesson_index = 0;

            // Loop through the total number of steps we expect (including both lessons and headers)
            for ($step_index = 0; $step_index < count($lessons) + count($section_headers); $step_index++) {

                // Check if a section header exists at this order
                $current_section = array_filter($section_headers, function($header) use ($step_index) {
                    return $header['order'] == $step_index;
                });

                // If section header exists, display it
                if (!empty($current_section)) {
                    $current_section = reset($current_section); // Get the first matched header
                    $output .= '<li class="course-section-header" style="margin-bottom: 10px; padding: 20px;">';
                    $output .= '<h4>' . esc_html($current_section['post_title']) . '</h4>';
                    $output .= '</li>';
                    continue;
                }

                // If not a header, show a lesson with completion status and current lesson styling
                if ($lesson_index < count($lessons)) {
                    $lesson_post = $lessons[$lesson_index];
                    
                    // Check if the lesson is completed
                    $is_completed = learndash_is_lesson_complete($user_id, $lesson_post->ID);
                    $circle_color_class = $is_completed ? 'completed' : 'not-completed';

                    // Check if this is the current lesson
                    $current_lesson_class = ($lesson_post->ID == $current_lesson_id) ? 'current-lesson' : '';

                    $output .= '<li class="lesson-item ' . $circle_color_class . ' ' . $current_lesson_class . '" style="margin-bottom: 10px; padding: 20px;">';
                    $output .= '<span class="lesson-circle"></span>';
                    $output .= '<a href="' . get_permalink($lesson_post->ID) . '">' . esc_html($lesson_post->post_title) . '</a>';
                    $output .= '</li>';
                    $lesson_index++;
                }
            }

            // Add quizzes at the end
            $quiz_query = new WP_Query(array(
                'post_type' => 'sfwd-quiz',
                'meta_key' => 'course_id',
                'meta_value' => $course_id,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'posts_per_page' => -1,
            ));

            if ($quiz_query->have_posts()) {
                while ($quiz_query->have_posts()) {
                    $quiz_query->the_post();
                    $output .= '<li class="lesson-item" style="margin-bottom: 5px;"><a href="' . get_permalink(get_the_ID()) . '">' . get_the_title() . '</a></li>';
                }
            }

            wp_reset_postdata(); // Reset the global post object

            $output .= '</ul>';
            $output .= '</div>';
            
            // Pass the course outline to JavaScript via wp_localize_script
            wp_localize_script('custom-lesson-script', 'lessonData', array(
                'lessonList' => $output
            ));
        }
    }
}

// Hook into wp_enqueue_scripts to register the JavaScript
add_action('wp_enqueue_scripts', 'register_custom_lesson_script');

function register_custom_lesson_script() {
    // Register and enqueue the custom JavaScript
    wp_register_script('custom-lesson-script', '', array(), '', true);
    wp_enqueue_script('custom-lesson-script');
    
    add_action('wp_footer', function() {
    ?>
        <script type="text/javascript">
           document.addEventListener("DOMContentLoaded", function() {
    // Find the entry-content div
    var entryContentDiv = document.querySelector('main .entry-content');

    if (entryContentDiv && typeof lessonData !== 'undefined') {
        // Create the new div element
        var newDiv = document.createElement('div');
        newDiv.className = 'custom-left-div';
        newDiv.style.backgroundColor = 'rgb(249 249 249)';
        newDiv.style.width = '350px';
        newDiv.style.height = 'auto';
        newDiv.style.float = 'left';
        newDiv.style.marginRight = '20px';
        newDiv.style.marginTop = '0px';
        
        // Add the course outline from the localized variable
        newDiv.innerHTML = `
            <div class="dropdown-header" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin: 0;">Contenido del curso</h4>
                <img src="<?php echo plugin_dir_url(__FILE__) . 'assets/arrow.svg'; ?>" class="dropdown-arrow" style="width: 16px; height: 16px; transform: rotate(0deg); transition: transform 0.3s ease-in-out;">
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

        </script>
    <?php
    });
}

// Hook to add custom inline CSS for responsiveness and permanent styles
add_action('wp_head', 'add_responsive_and_permanent_styles');

function add_responsive_and_permanent_styles() {
    ?>
    <style>
        
        /* General Styles */
.single-sfwd-lessons main > *:nth-child(1) {
    border-bottom: 1px solid black !important;
    margin-bottom: 0 !important;
}

.single-sfwd-lessons main > *:nth-child(3) {
    margin-top: 50px;
}

.single-sfwd-lessons main > *:nth-child(4) {
    display: none;
}

/* Custom Left Div */
.custom-left-div {
    border-right: 1px solid #c0bebe;
    background-color: rgb(249, 249, 249);
    border-radius: 3px;
    width: 350px;
    float: left;
    margin-right: 20px;
    margin-top: 0px;
    height: auto;
    position: relative;
    border-bottom: 1px solid #c0bebe;
}

/* Course Outline - Add scroll behavior with a max height */
.course-outline {
    max-height: 500px; /* Set the maximum height for scrolling */
    overflow-y: auto; /* Enable vertical scrolling */
    padding-left: 0;
    margin-bottom: 0;
    border-bottom: none;
}

/* Customize scrollbar for course-outline */
.course-outline::-webkit-scrollbar {
    width: 8px; /* Width of scrollbar */
}

.course-outline::-webkit-scrollbar-thumb {
    background-color: #c0bebe; /* Scrollbar color */
    border-radius: 4px;
}

/* Section Headers */
li.course-section-header {
    margin-bottom: 0 !important;
    border-bottom: none !important;
    border-top: 1px solid #c0bebe !important;
    font-size: 14px;
    padding-left: 20px !important;
}

li.course-section-header > h4 {
    margin: 10px 0;
    padding: 0;
    font-size: 12px;
    text-transform: uppercase;
    font-family: sans-serif;
    font-weight: 800;
}

/* Lessons and Quizzes */
.course-outline li {
    font-size: 13px !important;
    padding: 10px 40px !important;
    margin-bottom: 0 !important;
}

.course-outline ul li:last-child {
    border-bottom: 1px solid #c0bebe;
}

/* Custom div header (Dropdown Header) */
.dropdown-header {
    position: sticky; /* Keeps the header fixed when scrolling */
    top: 0;
    background-color: #f9f9f9;
    z-index: 10;
    padding: 20px;
    border-bottom: 1px solid #c0bebe;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 46px;
}

.custom-left-div > h4 {
    padding-left: 20px;
    margin-top: 0;
}

/* Remove bottom margin from unordered lists */
ul {
    margin-bottom: 0;
    border-bottom: none;
    margin-top: 0px;

}

/* Adjust margins for post template part */
.wp-block-template-part > .wp-block-group {
    border-bottom: none !important;
} 

.course-outline>ul li:first-child {
    border-top: 0px !important;
}

/* Medium screens */
@media (max-width: 1360px) { 
    .single-sfwd-lessons .entry-content.alignfull.wp-block-post-content {
        margin-left: 300px !important;
    }
} 

/* Small screens */
@media (max-width: 970px) {
    main#wp--skip-link--target {
        display: flex;
        flex-direction: column;
    }

    .entry-content.alignfull.wp-block-post-content {
        margin-left: 0 !important;
    } 

    .custom-left-div {
        width: 500px !important;
        margin: auto !important;
        height: auto;
        margin-top: 30px !important;
        border-right: none !important;
        border-bottom: 0px !important;
    }

    .dropdown-content {
        display: none;
    }

    .dropdown-header {
        padding: 20px;
        border: 1px solid #c0bebe;
        border-radius: 6px;
    }
}

/* Extra small screens */
@media (max-width: 530px) {
    .dropdown-header {
        max-width: 350px;
        margin: auto;
    }

    .custom-left-div {
        max-width: 400px;
        margin-top: 30px !important;
    }
}

/* Base styles for the circle */
.lesson-item {
    display: flex;
    align-items: center;
}

.lesson-circle {
    display: inline-block;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    margin-right: 10px;
    flex-shrink: 0;
}

/* Gray circle for not completed lessons */
.lesson-item.not-completed .lesson-circle {
    background-color: #d3d3d3; /* Gray */
}

/* Yellow circle for completed lessons */
.lesson-item.completed .lesson-circle {
    background-color: #ffd700; /* Yellow */
}

/* Base styles for the circle */
.lesson-item {
    display: flex;
    align-items: center;
}

/* Gray circle for not completed lessons */
.lesson-item.not-completed .lesson-circle {
    background-color: #d3d3d3; /* Gray */
}

/* Yellow circle for completed lessons */
.lesson-item.completed .lesson-circle {
    background-color: #ffd700; /* Yellow */
}

/* Add background for the current lesson */
.lesson-item.current-lesson {
    background-color: #efefef; /* Light gray background for the current lesson */
}

/* Combine completed and current lesson */
.lesson-item.completed.current-lesson {
    background-color: #efefef; /* Same light gray background for current lesson */
}


.learndash-wrapper .ld-button {
    align-items: center;
    background-color: #f9f9f9;
    border: 0;
    border-radius: 20px;
    box-shadow: none;
    color: #2890e8;
    cursor: pointer;
    display: flex;
    font-family: inherit;
    font-size: .75em;
    font-weight: 500;
    height: auto;
    justify-content: center;
    line-height: 1.25em;
    margin: 0;
    max-width: 385px;
    opacity: 1;
    padding: 0em;
    text-align: center;
    text-decoration: none;
    text-shadow: none;
    text-transform: none;
    transition: opacity .3s ease;
    white-space: normal;
    width: 100%;
}

input.wpProQuiz_button {
    margin: auto !important;
}

    </style>
    <?php
}

add_filter('learndash_template', 'custom_quiz_result_template', 10, 5);

function custom_quiz_result_template($filepath, $name, $args, $echo, $return_file_path) {
    // Check if the quiz result box template is being loaded
    if ($name == 'quiz/partials/show_quiz_result_box.php') {
        // Define the path to the custom template in your plugin
        $custom_template_path = plugin_dir_path(__FILE__) . 'learndash/templates/quiz/partials/show_quiz_result_box.php';

        // If the custom template file exists, use it
        if (file_exists($custom_template_path)) {
            return $custom_template_path;
        }
    }
    // Return the original template if the custom one is not found
    return $filepath;
}

