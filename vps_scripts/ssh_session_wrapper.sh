#!/usr/bin/env bash
# SSH interactive console session wrapper.
# Args: INPUT_FIFO  OUTPUT_LOG  PID_FILE  SSH_HOST  ASKPASS_SCRIPT
INPUT_FIFO="$1"
OUTPUT_LOG="$2"
PID_FILE="$3"
SSH_HOST="$4"
ASKPASS="$5"

export SSH_ASKPASS="$ASKPASS"
export SSH_ASKPASS_REQUIRE=force   # OpenSSH 8.4+
export DISPLAY=dummy               # older OpenSSH fallback
export HOME=/tmp
export TERM=xterm-256color
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# Create the named pipe
mkfifo "$INPUT_FIFO" 2>/dev/null

# Open the FIFO in read-write mode. This avoids the open(2) blocking deadlock:
# O_RDWR on a FIFO succeeds immediately regardless of other openers, so SSH can
# then open the read end and PHP can open the write end without any delay.
exec 3<>"$INPUT_FIFO"

# Signal that we are ready (PHP polls for this file)
echo $$ > "$PID_FILE"

ssh \
  -o StrictHostKeyChecking=no \
  -o BatchMode=no \
  -o PasswordAuthentication=yes \
  -o ConnectTimeout=15 \
  -o UserKnownHostsFile=/dev/null \
  -o LogLevel=ERROR \
  -tt \
  "root@$SSH_HOST" < "$INPUT_FIFO" >> "$OUTPUT_LOG" 2>&1

# SSH exited — release the FIFO write-end holder and clean up
exec 3>&-
rm -f "$INPUT_FIFO" "$ASKPASS"
printf '\r\n\x1b[90m[Connection closed]\x1b[0m\r\n' >> "$OUTPUT_LOG"
