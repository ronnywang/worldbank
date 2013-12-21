#!/usr/bin/env RScript

## Collect arguments
cmd_args <- commandArgs(TRUE)

## Default setting when no arguments passed
if(length(cmd_args) < 1) {
  cmd_args <- c("--help")
}

## Help section
print_help <- function(){
  cat(
    "PC-AXIS data format converter
    
    Arguments:
    px_file         - input PC-AXIS file  
    csv_file        - output CSV file
    --help          - print this help msg
    
    Example:
    $ RScript px_parser.R  in.px out.csv
    $ ./px_parser.R in.px out.csv\n\n")
}

if("--help" %in% cmd_args) {
  print_help()
  q(save="no")
}

if(length(cmd_args) != 2) {
  cat("Expect getting 2 arguments.\n")
  print_help()
  q(save="no")
}

# Main Program Start HERE

if (!exists("cmd_args") || length(cmd_args) == 0) {
  px_path <- "PR0101A2A.px"
  csv_path <- "out.csv"
} else {
  px_path <- cmd_args[[1]]
  csv_path <- cmd_args[[2]]
}
cat("infile:", px_path, "\n")
cat("outfile:", csv_path, "\n")

require(pxR)
my.px.object <- read.px("PR0105A1A.px", encoding="big5")
my.px.data   <-  as.data.frame(my.px.object)
# write.csv(my.px.data, file="raw_px.csv", row.names=FALSE)

# column to English
colnames(my.px.data) <- c("type", "indicator", "time", "value")

# Long to Wide
require(reshape2)
# output
df.ind_expanded <- dcast(my.px.data, time ~ type + indicator, mean)
df.type_expanded <- dcast(my.px.data, time + type ~ indicator, mean)

write.csv(df.ind_expanded, csv_path, row.names=FALSE)
