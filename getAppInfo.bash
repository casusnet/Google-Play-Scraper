#!/bin/bash
  tongada=6
  total=33141
  for ((i=0; i< $total; i=$i+$tongada)); do

 		ini=$i
  		end=$(($tongada - 1))
		php itemsinfo.php $ini $end &
		wait %1 
	
	sleep 5
done

 echo 'Terminado' | mail -s "Rodolfo. tarea completada." hello@96levels.com

