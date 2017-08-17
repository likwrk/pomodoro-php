#/usr/bin/env sh

API="http://pomodoro.dev/api"
ACTIVE=$(curl -X GET "$API/timers" 2>/dev/null)
TYPE=$(echo $ACTIVE | jq .type)
TIMELEFT=$(echo $ACTIVE | jq .timeleft)
TIMER_NAME=$(echo $ACTIVE | jq .name)
ICON_STOP="$HOME/dzen2-icons/empty.xbm"
ICON_POMODORO="$HOME/dzen2-icons/full.xbm"
ICON_BREAK="$HOME/dzen2-icons/half.xbm"
CURL_NEXT_TIMER="curl -X POST $API'/timers/start'"
CURL_NEXT_POMODORO="curl -X POST $API'/timers/start/pomodoro'"
CURL_NEXT_BREAK="curl -X POST $API'/timers/start/short'"
CURL_NEXT_LONG="curl -X POST $API'/timers/start/long'"
ZERO="0"
if [[ $TYPE == "null" ]]; then
    ICON=$ICON_STOP
    MINUTES=0
    SECONDS=0
else
    MINUTES=$(echo "$TIMELEFT / 60" | bc)
    SECONDS=$(echo "$TIMELEFT - $MINUTES * 60" | bc)

    if [[ $TIMELEFT -lt 1 ]]; then
        notify-send "Pomodoro timer" "$TIMER_NAME timer is done!" -i $HOME/.themes/pomodoro.png
    fi
fi

if [[ $SECONDS -lt 10 ]]; then
    SECONDS2="$ZERO$SECONDS"
else
    SECONDS2="$SECONDS"
fi

if [[ $TYPE -eq 1 ]]; then
    ICON=$ICON_POMODORO
fi

if [[ $TYPE -gt 1 ]]; then
    ICON=$ICON_BREAK
fi

echo "^ca(1, $CURL_NEXT_TIMER)^ca(2, $CURL_NEXT_BREAK)^ca(3, $CURL_NEXT_BREAK)^i($ICON) $MINUTES:$SECONDS2 ^ca()^ca()^ca()"
