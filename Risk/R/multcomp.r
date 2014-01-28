#! /usr/bin/Rscript --vanilla

#  install.packages("multcomp")
# Download tar from http://cran.r-project.org/web/packages/nparcomp/nparcomp.pdf
# sudo R CMD INSTALL nparcomp_2.0.tar.gz

library("rjson")
library("nparcomp")

args = commandArgs(TRUE)

bugginess <- fromJSON(args[2])
quantile = fromJSON(args[3])

df <- data.frame(bugginess, quantile)

res <- mctp( df$bugginess~df$quantile, data=df, type="Tukey",asy.method="fisher")

print(res$Analysis)




