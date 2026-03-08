<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'login_title'  => 'Admin Login',
        'email'        => 'Email Address',
        'password'     => 'Password',
        'remember'     => 'Remember me',
        'login_button' => 'Sign In',
        'logout'       => 'Sign Out',
        'failed'       => 'These credentials do not match our records.',
        'not_admin'    => 'This account does not have admin access.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */
    'nav' => [
        'dashboard'    => 'Dashboard',
        'competitions' => 'Competitions',
        'back_to_site' => 'Back to Site',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'title'             => 'Admin Dashboard',
        'welcome'           => 'Welcome, :name',
        'total'             => 'Total Competitions',
        'setup'             => 'In Setup',
        'active'            => 'Active',
        'ended'             => 'Ended',
        'total_contestants' => 'Total Contestants',
        'view_all'          => 'View All Competitions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Competitions Index
    |--------------------------------------------------------------------------
    */
    'competitions' => [
        'title'         => 'All Competitions',
        'search'        => 'Search by room code or title',
        'filter_status' => 'Filter by status',
        'all_statuses'  => 'All Statuses',
        'status_setup'  => 'Setup',
        'status_active' => 'Active',
        'status_ended'  => 'Ended',
        'from_date'     => 'From date',
        'to_date'       => 'To date',
        'filter_button' => 'Filter',
        'reset_filters' => 'Reset',
        'col_room'      => 'Room Code',
        'col_title'     => 'Title',
        'col_judge'     => 'Judge',
        'col_contestants' => 'Contestants',
        'col_rounds'    => 'Rounds',
        'col_status'    => 'Status',
        'col_created'   => 'Created',
        'col_actions'   => 'Actions',
        'view'          => 'View',
        'empty'         => 'No competitions match the current filters.',
        'pagination_info' => 'Showing :from to :to of :total competitions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Competition Detail / Show
    |--------------------------------------------------------------------------
    */
    'competition' => [
        'title'            => 'Competition Details',
        'back'             => 'Back to List',
        'end_competition'  => 'End Competition',
        'end_confirm'      => 'Are you sure you want to end this competition? This action cannot be undone.',
        'already_ended'    => 'This competition has already ended.',
        'ended_success'    => 'Competition ended successfully.',
        'cannot_end'       => 'A competition in setup cannot be ended.',

        'room_code'        => 'Room Code',
        'status'           => 'Status',
        'created_at'       => 'Created',
        'started_at'       => 'Started',
        'ended_at'         => 'Ended',
        'not_started'      => 'Not started yet',
        'not_ended'        => 'Not ended yet',
        'judge_section'    => 'Judge Information',
        'judge_name'       => 'Judge Name',
        'judge_email'      => 'Judge Email',
        'unknown'          => 'Unknown',

        'contestants_section' => 'Contestants & Scores',
        'col_rank'          => 'Rank',
        'col_name'          => 'Name',
        'col_score'         => 'Score',
        'col_claimed'       => 'Joined At',
        'winner_badge'      => 'Winner',
        'unclaimed'         => 'Not joined',

        'rounds_section'    => 'Round History',
        'col_round_no'      => 'Round',
        'col_round_status'  => 'Status',
        'col_first_buzz'    => 'First Buzz',
        'col_buzz_opened'   => 'Opened At',
        'col_first_buzzed'  => 'First Buzz At',
        'col_resolved'      => 'Resolved At',
        'no_rounds'         => 'No rounds recorded.',
        'no_buzz'           => '—',

        'buzz_log_section'  => 'Buzz Attempt Log',
        'col_attempt_round' => 'Round',
        'col_attempt_contestant' => 'Contestant',
        'col_attempt_time'  => 'Time',
        'col_attempt_accepted' => 'Accepted',
        'col_attempt_reason'   => 'Rejection Reason',
        'accepted_yes'      => 'Yes',
        'accepted_no'       => 'No',
        'no_attempts'       => 'No buzz attempts recorded.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status badges (reuse across views)
    |--------------------------------------------------------------------------
    */
    'status' => [
        'setup'     => 'Setup',
        'active'    => 'Active',
        'ended'     => 'Ended',
        'pending'   => 'Pending',
        'locked'    => 'Locked',
        'completed' => 'Completed',
    ],

];
