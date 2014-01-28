#! /usr/bin/Rscript --vanilla

library(rjson)
library(RWeka)
library(rJava)

args = commandArgs(TRUE)

header = fromJSON(args[2])
values = fromJSON(args[3])
cache_path = fromJSON(args[4])

# Create data frame for new values
values = as.matrix(values)
values = t(values)
colnames(values) = header
df = data.frame(values)

load(cache_path)

print("Predicted quantile is:")
predict(m, df)