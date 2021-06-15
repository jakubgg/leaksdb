# Replicate a directory structure with only the first 100 lines of each file

input=$1
output=$2

mkdir -p $output

for file in $(find "$input" -type f -name '*.txt' -or -name '*.csv'); 
do 
    outputFile="${file/$input/$output}"
    mkdir -p `dirname "$outputFile"`
    head -100 "$file" > "$outputFile"
done