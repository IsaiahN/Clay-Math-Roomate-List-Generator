# P-Vs-NP--Student-Roomate-List-Generator
Proposed Solution to the Student Dorm Matching Problem at https://www.claymath.org/millennium-problems/p-vs-np-problem

"Suppose that you are organizing housing accommodations for a group of four hundred university students. 
Space is limited and only one hundred of the students will receive places in the dormitory. 
To complicate matters, the Dean has provided you with a list of pairs of incompatible students, and requested that no pair from this list appear in your final choice. 

This is an example of what computer scientists call an NP-problem, since it is easy to check if a given choice of one hundred students proposed by a coworker is satisfactory (i.e., no pair taken from your coworker's list also appears on the list from the Dean's office)," - https://www.claymath.org

-------------------

Possible Solution:

The Deans’ list of bad pairings is the key to solving this problem.

Think of the list as a ranking.

Each time a student shows up on the dean’s bad match list, increase their ranking by one.
You’ll end of with a list of roommate applicants ranging from rank zero (can be paired with anyone) to more (People who repeatedly are on the list and require alot of checks to be paired with others.)

Because the roommates are 2 to a room in this case, we can pair anyone that’s at rank zero (no banned relations) 
with higher ranked roommates. 

Then you remove all instances of the students from the the student-student pair that are still in the ban list.
example: If Raisa M, Ruby W is an acceptable matching pair, you may be able to remove hundreds of instances from the list.

Essentially with every new accepted pairing, you steadily reduce the amount of available candidates and reduce the number of banned pairings that you need to compare.





