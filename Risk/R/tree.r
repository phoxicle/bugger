#! /usr/bin/Rscript --vanilla

# See RWeka doc http://cran.r-project.org/web/packages/RWeka/RWeka.pdf

library("rjson")

library("rJava")
library("RWeka")
library("cvTools")

args = commandArgs(TRUE)

csv_path = fromJSON(args[2])
training_split = fromJSON(args[3])
cache_path = fromJSON(args[4])


print(csv_path)
csv = read.csv(csv_path)

# Split training and test data
#sample_size = floor( training_split * nrow(csv) )

# Seed to make results reproducible. Leave out to keep random each time.
# set.seed(123)

#sample_indices = sample(seq_len(nrow(csv)), size=sample_size)

#trainset = csv[sample_indices, ]
#testset = csv[-sample_indices, ]

# Ten-fold validation
num_folds = 10
folds = cvFolds(nrow(csv), K=num_folds)

for(i in 1:num_folds) {
	
	trainset <- csv[folds$subsets[folds$which != i], ]
	testset <- csv[folds$subsets[folds$which == i], ]

	# Train model
	
	m = J48(bugginess ~ ., data=trainset)
	
	# Evaluate with test data
	if(nrow(testset) > 0) {
		print(paste("FOLD ", i))
	    print(evaluate_Weka_classifier(m, newdata=testset))
	}
}

# Save the (most recent) predicted model, if desired
if(!is.null(cache_path)) {

    # jcache Needed for this object type, see http://r.789695.n4.nabble.com/How-to-save-load-RWeka-models-into-from-a-file-td870876.html
    rJava::.jcache(m$classifier)

    save(m, file=cache_path)
}



# Print directed graph

dot_path = 'decision_tree_model.dot'

sink(dot_path)
write_to_dot(m)
sink()



print('Decision tree graph saved in dot file (requires Graphviz to view) has been saved in your current working directory as:')
print(dot_path)