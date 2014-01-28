#! /usr/bin/Rscript --vanilla

library("rjson")

args = commandArgs(TRUE)

values = fromJSON(args[2])
probabilities = fromJSON(args[3])

res = quantile(values, probs=probabilities)

print(res)




