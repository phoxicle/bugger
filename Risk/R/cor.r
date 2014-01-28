#! /usr/bin/Rscript --vanilla

library("rjson")

args = commandArgs(TRUE)

bugginess = fromJSON(args[2])
values = fromJSON(args[3])

print(cor.test(bugginess, values, method="pearson"))
print(cor.test(bugginess, values, method="kendall"))

# exact flag suppresses a warning
print(cor.test(bugginess, values, method="spearman", exact=FALSE))




