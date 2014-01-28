#########
# see how many commits selected by 2 (bisect) also exist when selected by 1 (diff)

SELECT b.*, d.determined_by, COUNT(*) as determined_by_diff
FROM
(SELECT * FROM risk_clues WHERE determined_by=2) b
LEFT JOIN
(SELECT * FROM risk_clues WHERE determined_by=1 GROUP BY commit_id, ticket_id) d
ON b.commit_id=d.commit_id AND b.ticket_id=d.ticket_id
GROUP BY d.determined_by;

# result: 125 determined only by bisect, 117 determined by both

#####
# get average number of commits found by diff for a single commit/ticket tuple

SELECT *, AVG(count) FROM
(SELECT *, COUNT(*) as count FROM risk_clues WHERE determined_by=1
GROUP BY commit_id, ticket_id) t
;

# result: 9.6857


###################
# get max number of commits found for a ticket

SELECT MAX(count) FROM (
	SELECT *, COUNT(*) as count FROM risk_clues WHERE determined_by=1
	GROUP BY ticket_id
) t
;

#  21700, commit seems to be something about Tombstone::automatic in july

####
# get max number of commits found for a ticket

SELECT * FROM (
	SELECT *, COUNT(*) as count FROM risk_clues WHERE determined_by=1
	GROUP BY ticket_id
) t
WHERE count > 100
;

# result: 60
# > 50: 151
# > 10: 695



###################
# get max number of UNIQUE commits found by diff for a single commit/ticket tuple

SELECT *, MAX(count)
FROM (
	SELECT *, COUNT(*) as count FROM (
		SELECT * FROM risk_clues WHERE determined_by=1 GROUP BY commit_id, ticket_id
	) t
	GROUP BY ticket_id
) m
;

# result: 69

####
# get tickets where number of UNIQUE commits found by diff > 50

SELECT *
FROM (
	SELECT *, COUNT(*) as count FROM (
		SELECT * FROM risk_clues WHERE determined_by=1 GROUP BY commit_id, ticket_id
	) t
	GROUP BY ticket_id
) m
WHERE m.count > 50
;

# > 50: 4
# > 10: 31


#####
 # get average number of commits found for commits matching a bisect

 SELECT *, AVG(count) FROM (
 SELECT b.*, d.determined_by as determined_by_diff, COUNT(*) as count
 FROM
 (SELECT * FROM risk_clues WHERE determined_by=2) b
 LEFT JOIN
    (SELECT * FROM risk_clues WHERE determined_by=1) d
 ON b.commit_id=d.commit_id AND b.ticket_id=d.ticket_id
 WHERE d.determined_by IS NOT NULL
  GROUP BY commit_id, ticket_id) t;

  # result: 5.094


  #####
  # get number of commits matching bisect where only one result was returned

  SELECT b.*, d.determined_by, d.count as determined_by_diff
  FROM
  (SELECT * FROM risk_clues WHERE determined_by=2) b
  LEFT JOIN
    (SELECT *, COUNT(*) as count FROM risk_clues WHERE determined_by=1 GROUP BY commit_id, ticket_id) d
  ON b.commit_id=d.commit_id AND b.ticket_id=d.ticket_id
  WHERE d.count=1;

  # result: 53 rows (out of 117) => 117-53=64 cases where bisect return but ambiguous.
  # d.count IS NOT NULL AND d.count < 5 commits: 86 rows
  # > 20: 4 rows, > 10: 11 rows

### helper view
CREATE VIEW `risk_v_buggy_commits` AS
select i.*, c.hash, c.date from risk_clues i
join `risk_commits` c
on(i.commit_id = c.id);


########
  # how much will the 1-1 bisect work if we only took the latest commit?

  # use view that joins commits with int-commits so it includes the commit date
  SELECT x.*, COUNT(*), y.id
  FROM
  (SELECT * FROM risk_clues WHERE determined_by=2) x
  LEFT JOIN
    (
  	# get the latest commit in each group
  	SELECT b.* FROM risk_v_buggy_commits b
  	JOIN (
  		# select the latest commit date from each for each ticket
  		SELECT t.ticket_id, MAX(date) as latest_date FROM
  		risk_v_buggy_commits t
  		WHERE determined_by=1
  		GROUP BY ticket_id

  	) d
  	ON d.ticket_id=b.ticket_id AND d.latest_date=b.date
  	GROUP BY ticket_id, commit_id
  ) y
  ON x.commit_id=y.commit_id AND x.ticket_id=y.ticket_id
  GROUP BY y.id IS NULL
  ;


  # result, 100 with/142 without
  # So, taking the latest result resolves the ambiguity from the DIFF results correctly in 100-64= 36 cases.
  #	This means 117-100=17 cases were disambiguated incorrectly by taking the latest commit
  # Overall, this would mean 100/242=41% commits are guessed exactly  by the DIFF method (taking BISECT as ground truth)


  ###########
  # how much will the 1-1 bisect work if we only took the commit which changed the most lines?

  # join bisected commits with the diff-found commits with maximal counts
  SELECT b.*, r.count, COUNT(*) FROM
  (SELECT * FROM risk_clues WHERE determined_by=2) b
  LEFT JOIN (
  	#select the commits which have this max count
  	SELECT d.* FROM
  	(SELECT *, COUNT(*) as count FROM risk_clues WHERE determined_by=1 GROUP BY ticket_id, commit_id) d
  	JOIN (
  		# select the max count
  		SELECT ticket_id, MAX(count) as max_count FROM
  			# calculate counts for each commit/ticket tuple
  			(SELECT t.ticket_id, t.commit_id, COUNT(*) as count FROM
  			risk_clues t
  			WHERE determined_by=1
  			GROUP BY ticket_id, commit_id) t
  		GROUP BY ticket_id
  	) m
  	ON d.ticket_id=m.ticket_id AND d.count=m.max_count
  ) r
  ON b.ticket_id=r.ticket_id AND b.commit_id=r.commit_id
  GROUP BY r.count IS NULL
  ;

  # 242 rows (so no duplicates?), 106 with, 136 without
  # So, went from 53 exact matches to 106 if we take the commits with most lines found by diff method.
  #	So, taking the result with most lines resolves the ambiguity correctly in 106-64=42 cases
  #	This means 117-106=11 cases were disambiguated incorrectly
  # Overall, this would mean 106/242=44% commits are guessed exactly by the DIFF method (taking BISECT as ground truth)


######################
# BUGGINESS

# Number of commits with bugginess values greater than...

SELECT COUNT(*) FROM risk_commits WHERE bugginess > 0.1;


# > 0.1 : 2673
# > 0.2 : 2366
# > 0.5 : 1266
# > 1 : 500
# > 3 : 69
# > 5 : 19
# > 10 : 5
# = 0 : 9846

# Max bugginess value

SELECT MAX(bugginess) FROM risk_commits;

# res: 17.09


####################
# Metrics

# Get commits with all their metric values

SELECT m.id as commit_id, m.bugginess, lines_added, num_patchsets, cc FROM
risk_commits as m
LEFT JOIN
(SELECT *, `value` as lines_added FROM risk_metrics WHERE `key`='lines_added') l
ON m.id=l.commit_id
LEFT JOIN
(SELECT *, `value` as num_patchsets FROM risk_metrics WHERE `key`='num_patchsets') p
ON m.id=p.commit_id
LEFT JOIN
(SELECT *, `value` as cc FROM risk_metrics WHERE `key`='cc') c
ON m.id=c.commit_id
;

