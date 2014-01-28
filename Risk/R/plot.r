#! /usr/bin/Rscript --vanilla

library("rjson")

args = commandArgs(TRUE)

values = fromJSON(args[2])
bugginess = fromJSON(args[3])
key = fromJSON(args[4])

file_path = paste(key,'png', sep='.')

png(file_path)
plot(values, bugginess, xlab=key, ylab='bugginess')
dev.off()

print(file_path)