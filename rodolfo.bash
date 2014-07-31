for ((i=0; i< 34; i++)); do
  sudo php googleplay.php $i
	wait
done

 echo 'He acabado!' | mail -s "Rodolfo. tarea completada." hello@96levels.com

