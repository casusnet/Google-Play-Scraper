#!/bin/bash
  tongada = '2500'

for ((i=0; i< 8; i++)); do
	  ini = '$i*2500'
  		end = $ini + $tongada
	sudo php itemsinfo.php $ini $end
	wait
	 echo 'Se han recogido los datos de $(($i*2500)) apps' | mail -s "Rodolfo. tarea completada." hello@96levels.com
	sleep 3600
done

 echo 'Terminado' | mail -s "Rodolfo. tarea completada." hello@96levels.com

