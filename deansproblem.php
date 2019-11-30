<?php
	/* Author: Isaiah Nwukor 
	*  Created: 11/22/19-11/29/19
	*  Description: Student Pairing List Generator.
	*/
    //ini_set('display_errors', 1); 
    //ini_set('display_startup_errors', 1); 
    ini_set('memory_limit', '256M');
    error_reporting(E_ALL);
    include 'Combinatorics.php';
    
	/* Variable List */
    $r = 2;    	// R = # of roomates to a room/pairing
    $k = 400;	// K = # of students applicants
	$a = 100; 	// A = # of choosen applicants
    $b = 60000; 	// B = # of banned pairings
	
    $q = $a/$r; // K = # of required pairings
    $q_list = array(); // The List of approved students.

	// Try SQL connection
	try {
		$servername = "localhost";
		$username = "root";
		$password = "";
		
		$conn = new PDO("mysql:host=$servername;dbname=students", $username, $password);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->query("SELECT name FROM `dean_list` ORDER BY RAND() LIMIT ".$k);
		$applicants = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
	catch(PDOException $e)
    {
		echo "Connection failed: " . $e->getMessage();
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
	
    echo '
    <h1>Student Booking Problem</h1>
    <p>Student List ('.$k.' Applicants | '.$r.' Students Per Room | '.$a.' Applicants Can Be Chosen  | '.$q.' Acceptable Pairings | '.$b.' Dean\'s Banned Pairings List)</p>
    
    <h3>Applicant List: '.$k.' Students:</h3>
    <textarea style="width:40%;height:400px;">';
    
    $ranked_list = array();
	// prints list of applicants & sets their initial ranked values to zero.
    for ($i = 0; $i < count($applicants); $i++) {
        echo $applicants[$i]."\n";
        $ranked_list[$applicants[$i]] = 0;
    }
    echo '</textarea>';
    
    // Creates the Combination of all possible combinations of students
    $combinatorics = new Math_Combinatorics;
    $ban_relations = $combinatorics->combinations($applicants, $r);
	
    /* Debug */
	//print_r($ban_relations);
    echo '
    <h3>All-Combinations of '.$k.' applicants ('.count($ban_relations).' Pairings Found)</h3>
    <p> This is using all possible combinations to create a list of bad pairings to pull from.</p>
    <br><textarea style="width:40%;height:400px;">';
    
    foreach($ban_relations as $p) {
      echo join(', ', $p), "\n"; 
    }
    
	
    /*
	Edge Case Limit Note: 
	
	If # of bad pairings reaches fairly close to the total amount,
	you get an edge case where some of the acceptable list combinations are possible, but only (with replacement).
	So if there should be ex: 20/25 Bad Pairings (total combinations).
	
	Even if 5 combinations are available, and they are not overlapping, by selecting one combo of student, you remove them from the list.
	So in that list of 5 combinations there may only be 2-3 combo pairs possible.
	
	When this happens, regardless what the number of # students, #needed pairs, or # of banned pairs, is if you end up in this ratio, the program will run out of memory (on certain machines.
	This generally leaves a minor percentage of possible matches remaining undiscovered.
	
	To test this in more detail, reduce k (number of students), $a (number of students needed) and $b (banned list pairing limit), you can also try to increase your memory limit (line 4)
	
    $r = 2;    	// R = # of roomates to a room/pairing
    $k = 25;	// K = # of students applicants
	$a = 8; 	// A = # of choosen applicants
    $b = 289; 	// B = # of banned pairings
	
	You should see that in most cases, it can sometimes get every combination thats possible, or run out of memory.
	
	---
	In all other cases, if there are enough combinations possible, the program should generate the list fine with few issues.
	
	*/
    echo '</textarea>
    <h3>Deans Bad List of Groupings ('.$b.' / '.count($ban_relations).' Pairs) </h3>
    <p>This is using a shuffled selection of the "full combo list" to create a dean ban list.</p>
    <br><textarea style="width:40%;height:400px;">';
    
    // The Closer to realistic set of deans ban list.
    // Randomize and Slice a Selection of the Total Combinations to make a realistic Ban List (subset of total combination list).
    shuffle($ban_relations);
    $trim_ban_relations = array_slice($ban_relations, 0, $b);
    // echo '<pre>'.print_r($trim_ban_relations, true).'</pre>';
    
    foreach($trim_ban_relations as $banned) {
      echo join(', ', $banned), "\n"; 
    }
    
    echo '</textarea>';
     
	//Create Ranked List and Sort List of Applicants By # of Bad Relations
    $ranked_list = rank_users($trim_ban_relations);
    
    pair_delist($ranked_list, $q_list, $q, $trim_ban_relations, $applicants);
    
	
	
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
        
        foreach ($active_ban_relations as $ban_pair) {
        
            $bf_key      = array_key_first($ban_pair);
            $bl_key     = array_key_last($ban_pair);
              
            if (
            (($temparr[0] == $ban_pair[$bf_key]) && ($temparr[1] == $ban_pair[$bl_key])) || 
            (($temparr[0] == $ban_pair[$bl_key]) && ($temparr[1] == $ban_pair[$bf_key]))
            ) {
			   /* Debug */	
               //echo "<br>found a similarity between ((".$temparr[0]." == ".$ban_pair[$bf_key].") && (".$temparr[1]." == ".$ban_pair[$bl_key].")) || ((".$temparr[0]." == ".$ban_pair[$bl_key].") && (".$temparr[1]." == ".$ban_pair[$bf_key]."))<br>";
                return false;
            }
        }
        return true;
    }
   
    
    function pair_delist($ranked_list, $q_list, $q, $active_ban_relations,$applicants){
        /* Debug */
		// echo '<pre>'.print_r($ranked_list, true).'</pre>';
	    // echo '<pre>'.print_r($q_list, true).'</pre>';
        
		//  Group List By Ranks
        $rank_group = rank_grouping($ranked_list);
        
        $f_key      = array_key_first($rank_group);
        $l_key      = array_key_last($rank_group);
        
        
        if (count($q_list) >= $q) {
            // If count of qualified list >= quota size
            echo "<h1>Final List of Acceptable Roomates:</h1>";
            $list = "";
            foreach ($q_list as $accepted_pairing) {
                $list .= implode(', ', $accepted_pairing)."<br>";
            }
            echo "<h3> List of accepted Pairings</h3>".$list;
			
			   /* echo "<h4>remaining ban list</h4><pre>".print_r($active_ban_relations, true)."</pre>";
				
				echo ' <br>Remaining Ban List<br><textarea style="width:40%;height:400px;">';
				$list = "";
				foreach ($active_ban_relations as $ban_pairing) {
					$list .= implode(', ', $ban_pairing)."\n";
				}
			
				echo $list;
				
				echo ' </textarea>';*/
        } else if ($f_key == $l_key) {
           /* when all the keys left are existing in same rank list level - 
              these are the 'worst' problems to sort for the program.
           */
            for ($i = 0; $i < (count($rank_group[$f_key])/2); $i+=2) {
                // Take all the lowest value keys applicants and combine them with the highest value keys applicants . IE: add them to the completed approved list.
                
                    //When array doesnt intersect with the ban list, then add it as approved.
                    $temparr = array($rank_group[$f_key][$i],$rank_group[$f_key][$i+1]);
					//echo '<br>Testing pair: '.implode(', ', $temparr);
                    if (($f_key == 0) || pairingCheck($temparr,$active_ban_relations) == true) {
                        //echo "<h3>New Pairing Accepted: ".$rank_group[$f_key][$i].", ".$rank_group[$f_key][$i+1]."</h3>";
                        $q_list[] = $temparr;
                        //$ranked_list[$rank_group[$f_key][$i]] = -1;
                        //$ranked_list[$rank_group[$l_key][$i]] = -1;
                        unset($ranked_list[$rank_group[$f_key][$i]]);
                        unset($ranked_list[$rank_group[$f_key][$i+1]]);
                        unset($rank_group[$f_key][$i]);
                        unset($rank_group[$f_key][$i+1]);
                        
                        // If it makes it past here, its returned all the checks, and should now remove all occurences from the active ban list.
                        
                        $rf_key = array_key_first($temparr);
                        $rl_key = array_key_last($temparr);
                        $count = 0;
                        foreach ($active_ban_relations as $bankey => $ban) {
                            
                                    
                                   //echo '<pre>Checking Pairing: '.print_r($temparr, true).'</pre><br>';
                                   //echo '<pre>Testing against ban pair: '.print_r($ban, true).'</pre><br>--<br>';
                                    
                                if (in_array($temparr[$rf_key], $ban) || in_array($temparr[$rl_key], $ban)) {
                                    /*
                                    $count++;
                                    echo "<h3>found a match to remove: ".$count."</h3>";
                                    echo '<pre>Checking Pairing: '.print_r($temparr, true).'</pre>';
                                    echo '<pre>Ban List Item: '.print_r($ban, true).'</pre>';
                                    */
                                    unset($active_ban_relations[$bankey]); 
                                }
                        }
                        
                    } else {
						// When the 1st item in the first group is a bad match with the 1st item in last group, shuffle the ranked_list
                        //echo "<br>this matching: ".$rank_group[$f_key][$i]." and ".$rank_group[$l_key][$i+1]." was banned <br>";
                       
						
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
                    //echo "list didnt meet requirements, here is the squashed qlist";
                    //echo '<pre>'.print_r($squashed_list, true).'</pre>';
                    
                    
                    //echo "here is the original applicant list";
                    //echo '<pre>'.print_r($applicants, true).'</pre>';
                    
                    //echo "After doing an array diff with the original list, heres some students that are level 0 we can pair up.";
                    
                    $total_remaining_apps = array_diff($applicants, $squashed_list);
                    //echo '<pre>'.print_r($total_remaining_apps, true).'</pre>';
                    
                    //echo "And since we know they werent generated from the ban list, then these are all level 0 and can be combined with each other.<br> so we randomly shuffle slice this array by number remaining *2";
                    shuffle($total_remaining_apps); 
                    $selected_apps = array_slice($total_remaining_apps, 0, ((($q-count($q_list))*2)));
                    //echo '<pre>'.print_r($selected_apps, true).'</pre>';
                    
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
					//echo '<br>Testing pair: '.implode(', ', $temparr);
                    if (($f_key == 0) || pairingCheck($temparr,$active_ban_relations) == true) {
                        
                        
                        //echo "<h3>New Pairing Accepted: ".$rank_group[$f_key][$i].", ".$rank_group[$l_key][$i]."</h3>";
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
                            
                                    //echo '<pre>Checking Pairing: '.print_r($temparr, true).'</pre><br>';
                                    //echo '<pre>Testing against ban pair: '.print_r($ban, true).'</pre><br>--<br>';
                            
                            $count++;
                                if (in_array($temparr[$rf_key], $ban) || in_array($temparr[$rl_key], $ban)) {
                                    /*
                                    $count++;
                                    echo "<h3>found a match to remove: ".$count."</h3>";
                                    echo '<pre>Checking Pairing: '.print_r($temparr, true).'</pre>';
                                    echo '<pre>Ban List Item: '.print_r($ban, true).'</pre>';
                                    */
                                    unset($active_ban_relations[$bankey]); 
                                }
                        }
                        //echo "<br>checked student matching ".$count. " times: ".print_r($temparr, true);
                    } else {
                        //echo "<br>this matching: ".$rank_group[$f_key][$i]." and ".$rank_group[$l_key][$i]." was banned <br>";
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
?>