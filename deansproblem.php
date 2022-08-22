<?php
	/* Author: Isaiah Nwukor 
	*  Created: 11/22/19-11/30/19
	*  Updated: 08/21/22
	*  Description: Student Pairing List Generator V2. Uses inputs, and has a text file for default generation method.
	*/
    ini_set('display_errors', 1); 
    ini_set('display_startup_errors', 1); 
    ini_set('memory_limit', '512M');
    ini_set('max_input_vars', '2500');
    error_reporting(E_ALL);
    include 'Combinatorics.php';
    ?>
    
    <html>
    <head>
        <title>CMI | Roommate Sorting Problem Demo</title>
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" crossorigin="anonymous">
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
      <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    </head>
    <body>
       <div class="container">
    <?
    if (isset($_REQUEST["submit-button"])) {
        $combinatorics = new Math_Combinatorics;
        // If Form is Submitted with User Submitted Data
            $form_list = array(); //List of User Inputs
            foreach ($_REQUEST as $key => $value) {
                $form_list[htmlspecialchars($key)] = htmlspecialchars($value);
            }
        	/* Variable List - User Inputs */
            $r = intval($form_list["r"]); // R = # of Roommates to a room/pairing
            $k = intval($form_list["k"]); // K = # of students applicants
        	$a = intval($form_list["a"]); // A = # of choosen applicants
            $b = intval($form_list["b"]); // B = # of banned pairings
            $q = $a/$r;                   // Q = # of required pairings
            $q_list = array();            // The List of approved students.
            
            // Show all array values and processes
            $GLOBALS['show_debug'] = false;          
            if (isset($form_list['show_debug'])) {
                $GLOBALS['show_debug'] = true; 
            }
            
            if ($GLOBALS['show_debug']) {
                echo "<h3>Form List</h3><pre>";
                print_r($form_list);
                echo "</pre>";
            }
            
        // Extract List of Applicants to Array
        if (isset($form_list["applicants"]) && strlen($form_list["applicants"]) > 0) {
            $applicants = array_map('trim', explode(PHP_EOL, $form_list["applicants"])); // List of applicants from user
            $num_submissions = count($applicants);
            // Limit User Inputs to Max of their applicant entries
            
            $k = min($k, $num_submissions);     // K = # of students applicants
            
            
        	$rand_finalist_limit = (int) ($num_submissions * (rand(70, 100) / 100)); //(create a random percentage (between 70% - 100%) to generate number of accepted applicants )
        	$a = min($a, $rand_finalist_limit);     // A = # of choosen applicants
        	 
        	// B = # of Possible banned pairings based on permutation of n! / (n-r)!
        	// Also generate number of groupings based on percentage.
        	// Example 500 possible pairings * 90% will generate a ban list of 450 pairings.
        	$lower_perm = $k - $r; 
        	$factk = fact($k);
        	$factl = fact($lower_perm); 
            $q = (int)($a/$r);                         // Q = # of required pairings
            
            $b = (int)(($factk / $factl) - $q ); //Total possible pairings - required pairings = possible max banned list
            
            
        } else {
        	try {
            	/* Variable List - Default Values */
                $applicants_text = explode(PHP_EOL, file_get_contents("student_list.txt"));
                shuffle($applicants_text);
                $applicants = array_slice($applicants_text, 0, $k);
                
                if ($GLOBALS['show_debug']) {
                    echo "<h2>Applicants text from student_list.txt</h2><pre>".print_r($applicants, true)."</pre>";
                }
            }
        	catch(Exception $e)
            {
        		echo "Could Not Find File student_list.txt: " . $e->getMessage();
        		exit();
            }
        }
        // Extract or Generate List of Banned Pairings
        if (isset($form_list["ban_relations"]) && strlen($form_list["ban_relations"]) > 0) {
            $ban_relations_list = explode(PHP_EOL, $form_list["ban_relations"]); // List of applicants from user
            
            if ($GLOBALS['show_debug']) {
                echo "<h3>Form List</h3><pre>";
                print_r($ban_relations_list);
                echo "</pre>";
                
                 echo "<h3>applicants List</h3><pre>";
                print_r($applicants);
                echo "</pre>";
            }
            
            $ban_relations = array(); //List of User Inputs
            foreach ($ban_relations_list as $pairing) {
                $applicant = array_map('trim', explode(",", $pairing));
                
                if ($GLOBALS['show_debug']) {
                  echo "<h3>applicant pairing</h3><pre>";
                print_r($applicant);
                echo "</pre>";
                }
                
                $temp_arr = array();
                for ($v=0; $v < count($applicant); $v++) {
                   $key_app = array_search($applicant[$v], $applicants);
                   if ($key_app == false) {
                       echo "
                       <div class='alert alert-danger' role='alert'>
                       <p>Banned Pairing: <strong>".$pairing."</strong> has an applicant - <strong>".$applicant[$v]."</strong> that is not found in the original list of applicants.
                       <br/>Resubmit Your List with banned users that exist in the applicant </p>
                       </div>";
                       exit();
                   }
                   $temp_arr[$key_app] = $applicant[$v];
                }
                $ban_relations[] = $temp_arr;
            }
        } else {
        	try {
                // Create realistic set of deans ban list.
                // Generate all possible combinations of students based on number of students per pairing
                $ban_relations = $combinatorics->combinations($applicants, $r);
            }
        	catch(Exception $e)
            {
        		echo "Could Not Create Combination List: " . $e->getMessage();
        		exit();
            }
        }
        
       
        // Randomize and Slice a Selection of the Total Combinations to make a realistic Ban List (subset of total combination list).
        shuffle($ban_relations);
        $trim_ban_relations = array_slice($ban_relations, 0, $b);
        
    
        
        if ($GLOBALS['show_debug']) {
            echo '<h2>Debug - List of Banned Combinations</h2><pre>'.print_r($trim_ban_relations, true).'</pre>';
        }
       
    	// create list of ranked applicants & sets their initial ranked values to zero.
        $ranked_list = array();
        for ($i = 0; $i < count($applicants); $i++) {
            $ranked_list[$applicants[$i]] = 0;
        }
       
      
    	/* 
    	If the Number of students per pairing was > 2,
    	Then you would only need to make a function that breaks the pairings into groupings of 2, before processing them.
    	Ex:
    	
    	Banned pairing:
    	student_1, student_2, student_3
    	
    	would become :
    	student_1, student_3
    	student_1, student_2
    	student_3, student_2
    	*/
        
    	//Create Ranked List and Sort List of Applicants By # of Bad Relations
        $ranked_list = rank_users($trim_ban_relations);
        if ($GLOBALS['show_debug']) {
                echo '<h2>Debug - List of Ranked Users</h2><p>Create Ranked List and Sort List of Applicants By # of Bad Pairings each applicant is listed in</p>';
                echo "<pre>";
                print_r($ranked_list);
                echo "</pre>";
        }
     
        
         // Show Results
         renderApplicants($k, $r, $a, $q, $b, $applicants);
    	 renderCombinations($k, $ban_relations);
    	 renderBannedList($b, $ban_relations, $trim_ban_relations);
         pair_delist($ranked_list, $q_list, $q, $trim_ban_relations, $applicants);
    }
    
    
	
	
	/* Functions */ 
 
	// Create rank_list from banned applicant relationships
    function rank_users($arr_banned) {
        $ranked_list = array();
        foreach($arr_banned as $banned) {
			//check and increment first and last element of each random pair found in ban list.
           if (empty($ranked_list[$banned[array_key_first($banned)]])) {
                $ranked_list[$banned[array_key_first($banned)]] = 1;
            } else {
                  $ranked_list[$banned[array_key_first($banned)]]++;
            }
			
			if (empty($ranked_list[$banned[array_key_last($banned)]])) {
                $ranked_list[$banned[array_key_last($banned)]] = 1;
            } else {
                  $ranked_list[$banned[array_key_last($banned)]]++;
            }
        }
    asort($ranked_list);
    return $ranked_list;
    }
    
   // Splits ranked list into groupings based on # appearances on the ban list.
   function rank_grouping($arr) {
    $rank_sort = array();
      foreach ($arr as $applicant => $value) {
        $rank_sort[$value][] = $applicant;
    }
    return $rank_sort;
   }
   
    function pairingCheck($temparr,$active_ban_relations)
    {
        // If false, then pair is from ban list
        // If true then a match is not found and approved
        if ($GLOBALS['show_debug']) {
            echo '<h2>Debug - Pairing Check</h2><p>Checks Potential Pairing against Banned List of Pairings</p><hr>';
        }
        
        foreach ($active_ban_relations as $ban_pair) {
        
            $bf_key      = array_key_first($ban_pair);
            $bl_key     = array_key_last($ban_pair);
              
            if (
            (($temparr[0] == $ban_pair[$bf_key]) && ($temparr[1] == $ban_pair[$bl_key])) || 
            (($temparr[0] == $ban_pair[$bl_key]) && ($temparr[1] == $ban_pair[$bf_key]))
            ) {
			   if ($GLOBALS['show_debug']) {
                  echo "<p>found a similarity between ((".$temparr[0]." == ".$ban_pair[$bf_key].") && (".$temparr[1]." == ".$ban_pair[$bl_key].")) || ((".$temparr[0]." == ".$ban_pair[$bl_key].") && (".$temparr[1]." == ".$ban_pair[$bf_key]."))</p>";
			   }
                return false;
            }
        }
        return true;
    }
   
    
    function pair_delist($ranked_list, $q_list, $q, $active_ban_relations,$applicants){
        if ($GLOBALS['show_debug']) {
	     echo '<h4>Current List of Approved Students</h4><pre>'.print_r($q_list).'</pre>';
        }
		//  Group List By Ranks
        $rank_group = rank_grouping($ranked_list);
        $f_key      = array_key_first($rank_group);
        $l_key      = array_key_last($rank_group);
        
        if (count($q_list) >= $q) {
            // If count of qualified list >= quota size
            $list = "";
            foreach ($q_list as $accepted_pairing) {
                $list .= implode(', ', $accepted_pairing)."<br>";
            }
            
            $ban_list = "";
            foreach ($active_ban_relations as $b_pair) {
                $ban_list .= implode(', ', $b_pair)."<br>";
            }
            echo "<br/><div class='alert alert-success' role='alert'><h4>Final List of Acceptable Roommates (".count($q_list)." Pairings)</h4><hr/>".$list."</div>";
			
		   if ($GLOBALS['show_debug']) {
            echo "<hr><h6><div class='alert alert-warning' role='alert'><h4>Remaining Valid Ban Pairings list (".count($active_ban_relations)." Total Pairings)</h4><hr/>".$ban_list."</div>";
		   }
        } else if ($f_key == $l_key) {
           /* when all the keys left are existing in same rank list level - 
              these are the 'worst' problems to sort for the program.
           */
            for ($i = 0; $i < (count($rank_group[$f_key])/2); $i+=2) {
                // Take all the lowest value keys applicants and combine them with the highest value keys applicants . IE: add them to the completed approved list.
                
                    //When array doesnt intersect with the ban list, then add it as approved.
                    $temparr = array($rank_group[$f_key][$i],$rank_group[$f_key][$i+1]);
					 if ($GLOBALS['show_debug']) { 
					     echo '<br>Testing pair: '.implode(', ', $temparr);
					 }
                    if (($f_key == 0) || pairingCheck($temparr,$active_ban_relations) == true) {
                        
                        $q_list[] = $temparr;
                        if ($GLOBALS['show_debug']) { 
                            echo "<h3>New Pairing Accepted: ".$rank_group[$f_key][$i].", ".$rank_group[$f_key][$i+1]."</h3>";
                        }
                        unset($ranked_list[$rank_group[$f_key][$i]]);
                        unset($ranked_list[$rank_group[$f_key][$i+1]]);
                        unset($rank_group[$f_key][$i]);
                        unset($rank_group[$f_key][$i+1]);
                        
                        // If it makes it past here, its returned all the checks, and should now remove all occurences from the active ban list.
                        $rf_key = array_key_first($temparr);
                        $rl_key = array_key_last($temparr);
                        $count = 0;
                        foreach ($active_ban_relations as $bankey => $ban) {
                            
                                    $count++;
                                     if ($GLOBALS['show_debug']) { 
                                        echo '<pre>Checking Pairing: '.print_r($temparr).'</pre><br>';
                                        echo '<pre>Testing against ban pair: '.print_r($ban).'</pre><br>--<br>';
                                     }
                                    
                                if (in_array($temparr[$rf_key], $ban) || in_array($temparr[$rl_key], $ban)) {
                                    if ($GLOBALS['show_debug']) { 
                                        echo "<h3>found a match to remove: ".$count."</h3>";
                                        echo '<pre>Checking Pairing: '.print_r($temparr).'</pre>';
                                        echo '<pre>Ban List Item: '.print_r($ban).'</pre>';
                                    }
                                    unset($active_ban_relations[$bankey]); 
                                }
                        }
                        
                        if ($GLOBALS['show_debug']) { 
                            echo "<br>checked student matching ".$count. " times: ".print_r($temparr, true);
                        }
                        
                    } else {
						// When the 1st item in the first group is a bad match with the 1st item in last group, shuffle the ranked_list
                        if ($GLOBALS['show_debug']) { 
                            echo "<br>this matching: ".$rank_group[$f_key][$i]." and ".$rank_group[$l_key][$i+1]." was banned <br>";
                        }
                       
                        //https://stackoverflow.com/questions/4102777/php-random-shuffle-array-maintaining-key-value
                        //https://stackoverflow.com/users/2350217/mohamed-bouallegue
						uksort($ranked_list, function() { return rand() > getrandmax() / 2; });
                    }
                }
                if (count($q_list) < $q) {
                    /*  Edge Case: Ban list wasn't enough, so you'll need to 
						Take students from main pool that aren't already added and add them to the list.
					*/
                    $squashed_list = array_merge(...$q_list);
                    if ($GLOBALS['show_debug']) { 
                        echo "<h2>Ban list didnt meet requirements, here is the squashed qlist</h2>";
                        echo '<pre>'.print_r($squashed_list, true).'</pre>';
                        echo "<h2>here is the original applicant list</h2>";
                        echo '<pre>'.print_r($applicants, true).'</pre>';
                        echo "<h2>After doing an array diff with the original list, here are some students that are level 0 we can pair up.</h2>";
                    }
                    
                    $total_remaining_apps = array_diff($applicants, $squashed_list);
                    if ($GLOBALS['show_debug']) { 
                        echo '<pre>'.print_r($total_remaining_apps, true).'</pre>';
                        echo "<h2>And since we know they werent generated from the ban list, then these are all level 0 and can be combined with each other.<br> so we randomly shuffle slice this array by number remaining *2</h2>";
                    }
                    shuffle($total_remaining_apps); 
                    $selected_apps = array_slice($total_remaining_apps, 0, ((($q-count($q_list))*2)));
                    if ($GLOBALS['show_debug']) { 
                        echo '<pre>'.print_r($selected_apps, true).'</pre>';
                    }
                    
                    for ($i = 0; $i < (count($selected_apps)/2); $i+=2) {
                    // Take all the lowest value keys applicants and combine them with the highest value keys applicants . IE: add them to the completed approved list.
                        //When array doesnt intersect with the ban list, then add it as qualified.
                        $temparr = array($selected_apps[$i],$selected_apps[$i+1]);
                        $q_list[] = $temparr;
                    }
                }
                pair_delist($ranked_list, $q_list, $q, $active_ban_relations,$applicants);
        } else {
            // completed approved list is less than the student pair quota.
            if (empty($q_list) || count($q_list) < $q) {
                for ($i = 0; $i < min(count($rank_group[$f_key]),count($rank_group[$l_key])); $i++) {
                // Take all the lowest value keys applicants and combine them with the highest value keys applicants . IE: add them to the completed approved list.
                // Set both applicants to rank -1 on the active rank list.
                
                    //When array doesnt intersect with the ban list, then add it as approved.
                    $temparr = array($rank_group[$f_key][$i],$rank_group[$l_key][$i]);
					if ($GLOBALS['show_debug']) { 
					    echo '<br>Testing pair: '.implode(', ', $temparr);
					}
                    if (($f_key == 0) || pairingCheck($temparr,$active_ban_relations) == true) {
                        
                        
                        if ($GLOBALS['show_debug']) { 
                            echo "<h3>New Pairing Accepted: ".$rank_group[$f_key][$i].", ".$rank_group[$l_key][$i]."</h3>";
                        }
                        // Add approved pairing to qualified list, and remove it from the ranked list and ranked group.
                        $q_list[] = $temparr;
                        unset($ranked_list[$rank_group[$f_key][$i]]);
                        unset($ranked_list[$rank_group[$l_key][$i]]);
                        unset($rank_group[$f_key][$i]);
                        unset($rank_group[$l_key][$i]);
                        
                        // If it makes it past here, its returned all the checks, and should now remove all occurences from the active ban list.
                        
                        $rf_key = array_key_first($temparr);
                        $rl_key = array_key_last($temparr);
                        $count = 0;
                        foreach ($active_ban_relations as $bankey => $ban) {
                            if ($GLOBALS['show_debug']) { 
                                echo '<pre>Checking Pairing: '.print_r($temparr, true).'</pre><br>';
                                echo '<pre>Testing against ban pair: '.print_r($ban, true).'</pre><br>--<br>';
                            }
                            $count++;
                                if (in_array($temparr[$rf_key], $ban) || in_array($temparr[$rl_key], $ban)) {
                                    if ($GLOBALS['show_debug']) { 
                                        $count++;
                                        echo "<h3>found a match to remove: ".$count."</h3>";
                                        echo '<pre>Checking Pairing: '.print_r($temparr, true).'</pre>';
                                        echo '<pre>Ban List Item: '.print_r($ban, true).'</pre>';
                                    }
                                    unset($active_ban_relations[$bankey]); 
                                }
                        }
                        if ($GLOBALS['show_debug']) { 
                            echo "<br>checked student matching ".$count. " times: ".print_r($temparr, true);
                        }
                    } else {
                        if ($GLOBALS['show_debug']) { 
                            echo "<br>this matching: ".$rank_group[$f_key][$i]." and ".$rank_group[$l_key][$i]." was banned <br>";
                        }
                        // When the 1st item in the first group is a bad match with the 1st item in last group, shuffle the ranked_list
                          
                        //https://stackoverflow.com/questions/4102777/php-random-shuffle-array-maintaining-key-value
                        //https://stackoverflow.com/users/2350217/mohamed-bouallegue
						uksort($ranked_list, function() { return rand() > getrandmax() / 2; });
                    }
                }
            }
                pair_delist($ranked_list, $q_list, $q, $active_ban_relations,$applicants);
        }
    }
    	// Get Factorial 
	// https://www.geeksforgeeks.org/php-factorial-number/
	function fact($n){
        if ($n <= 1){return 1;} 
        else {return $n * fact($n - 1);}
    }  
    
    
    
     /* START - Template functions */
    
    function renderApplicants($k, $r, $a, $q, $b, $applicants) {
    echo '
    <h3 style="text-decoration:underline">Explained Process & Results</h3>
    <p>Student List ('.$k.' Applicants | '.$r.' Students Per Room | '.$a.' Applicants Can Be Chosen  | '.$q.' Acceptable Pairings)</p>
    
    <h6>Applicant List: '.$k.' Students:</h6>
    <textarea style="width:40%;" rows="7">';
      for ($i = 0; $i < count($applicants); $i++) {
        echo $applicants[$i]."\n";
      }
     echo '</textarea>';
    }
    
    function renderCombinations($k, $ban_relations) {
        echo '
        <br/><br/><h6>All-Combinations of '.$k.' applicants ('.count($ban_relations).' Possible Pairings Found)</h6>
        <p> This is using all possible combinations to create a list of bad pairings to pull from.</p>
        <textarea style="width:40%;" rows="7">';
        foreach($ban_relations as $p) {
            echo join(', ', $p), "\n"; 
        }
        echo '</textarea>';
    }
    
    function renderBannedList($b, $ban_relations, $trim_ban_relations) {
        echo '
        <br/><br/><h6>Deans Bad List of Groupings ('.min($b,count($ban_relations)).' / '.count($ban_relations).' Possible Pairs)</h6>
        <p>This shuffles the All-Combinations applicants list, and selects the first '.min($b,count($ban_relations)).' pairs to create a dean ban list.</p>
        <textarea style="width:40%;" rows="7">';
        foreach($trim_ban_relations as $banned) {
            echo join(', ', $banned), "\n"; 
        }
        echo '</textarea>';
        if (!$GLOBALS['show_debug'] || ($GLOBALS['show_debug']) == false) { 
            echo '<br><br><div class="alert alert-primary" role="alert">
            <h4>Processing Matches...</h4>
            <hr/>
            <em>Under the Hood...</em>
            <ul>
                <li>Reduction Technique #1: Pairing applicants with the highest entries on the ban list, with applicants, not on the banlist, to reduce complexity (that can pair together)</li>
                <li>Reduction Technique #2: Pairing Remaining Applicants with highest ban list entries, and lowest ban list entries (that can pair together)</li>
                <li>etc...</li>
            </ul></div>';
        }
        
    }
     /* END - Template functions */
?>



    <form action="/deansproblem.php" method="POST">
        <div class="rendered-form">
                <div class="header">
                    <h3>Roommate Matching Demo</h3>
                    <p>Solution to the Roommate Matching Puzzle from <a href="https://www.claymath.org/millennium-problems/p-vs-np-problem" title="Clay Math Institute">Clay Math Institute</a>
                    <br/>Source Code Available Via: <a href="https://github.com/IsaiahN/P-Vs-NP--Student-Roommate-List-Generator" title="Github @IsaiahN">Github</a></p>
                    <hr>
                </div>
                <div class="row">
                    <div class="field-r col">
                        <label for="r" class="label"># Roommates Per Room<span class="formbuilder-required">*</span></label>
                        <input type="number" placeholder="ex: 2" class="form-control" name="r" access="false" min="0" id="r" required="required" aria-required="true" value="<?php echo (isset($form_list["r"])) ? $form_list["r"] : '2'; ?>">
                    </div>
                    <div class="field-k col">
                        <label for="k" class="label"># of Student Applicants<span class="formbuilder-required">*</span></label>
                        <input type="number" placeholder="ex: 400" class="form-control" name="k" access="false" min="0" id="k" required="required" aria-required="true" value="<?php echo (isset($form_list["k"])) ? $form_list["k"] : '400'; ?>">
                    </div>
                    <div class="field-a col">
                        <label for="a" class="label"># Max Selected Finalists<span class="formbuilder-required">*</span><span class="tooltip-element" tooltip="Select the max number of students that can be chosen">?</span></label>
                        <input type="number" placeholder="ex: 100" class="form-control" name="a" access="false" min="0" id="a" title="Select the max number of students that can be chosen" required="required" aria-required="true" value="<?php echo (isset($form_list["a"])) ? $form_list["a"] : '100'; ?>">
                    </div>
                    <div class="field-b col">
                        <label for="b" class="label"># of Banned Pairings<span class="formbuilder-required">*</span><span class="tooltip-element" tooltip="Will Autogenerate a list of banned pairings.">?</span></label>
                        <input type="number" placeholder="ex: 70000" class="form-control" name="b" access="false" min="0" id="b" title="Will Autogenerate a list of banned pairings." required="required" aria-required="true" value="<?php echo (isset($form_list["b"])) ? $form_list["b"] : '70000'; ?>">
                    </div>
                      <div class="field-submit-button col">
                        <input type="checkbox" id="show_debug" name="show_debug">  
                        <label for="debug" class="label">Show Debug?</label><br/>
                        <button type="submit" class="btn-primary btn" name="submit-button" value="generate" access="false" style="primary" id="submit-button">Generate Pairings</button>
                    </div>
                </div>
                <div class="divider">
                        <hr><h4><em>Custom</em> - Use Your Own Applicants Lists:</h4>
                </div>
                <div class="row">
                    <div class="field-applicants col">
                        <div class="alert alert-info" role="alert">
                        <label for="applicants" class="label"><strong>Applicant's List <em>&mdash; Required</em></strong></label>
                        <textarea type="textarea" placeholder="Example:&#10;Daryl Park&#10;Benton Coker&#10;Shawn Russo&#10;James Brooks&#10...etc" class="form-control" name="applicants" access="false" rows="7" id="applicants"><? isset($form_list["applicants"]) ? $form_list["applicants"] : "";?></textarea>
                        <strong>Applicants, pairings, and finalists will be limited to number of entries you provide</strong>
                        </div>
                    </div>
                    <div class="field-ban_relations col">
                        <div class="alert alert-dark" role="alert">
                        <label for="ban_relations" class="label"><strong>Banned Roommate Pairings <em>&mdash; Optional</em></strong></label>
                        <textarea type="textarea" placeholder="Example:&#10;James Brooks, Daryl Park&#10;Carole Garner, Benton Coker&#10;Denny Cone, Norma Davis&#10;...etc" class="form-control" name="ban_relations" access="false" rows="7" id="ban_relations" title="List of banned pairings to not allow in this generated list."><? isset($form_list["ban_relations"]) ? $form_list["ban_relations"] : "";?></textarea>
                        <strong>If left blank, a randomized ban list will be made based on the applicant's list</strong>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-start">
                        <div class="field-submit-button col-3">
                            <button type="submit" class="btn-secondary btn btn-lg" name="submit-button" value="process" access="false" style="warning" id="submit-button">Process Custom Pairings</button>
                        </div>
                </div>
            </div>
    </form>
    </div>
<body>
</html>
