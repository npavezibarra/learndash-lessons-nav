<?php

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
                    return isset($header['order']) && $header['order'] == $step_index;
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
