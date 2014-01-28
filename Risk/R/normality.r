#! /usr/bin/Rscript --vanilla

library("rjson")

args = commandArgs(TRUE)

values = fromJSON(args[2])

print(shapiro.test(values))
