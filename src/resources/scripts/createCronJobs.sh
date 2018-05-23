if ! crontab -l | grep -q '{{ command }}'; then
  crontab -l | { cat; echo '{{ command }}'; } | crontab -
  echo 'Cron job created "{{ command }}" '
else
  echo 'Cron job already exists "{{ command }}"'
fi