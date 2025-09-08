Start:
======

- `docker compose up -d`
- Access the Kafka UI at `http://localhost:8080` 

How to send a message:
======================
- Access `http://localhost:8000/send-kafka` to produce a message in `MyTopic`

How to consume messages:
========================
- `docker compose exec php bash`
- `bin/console kafka:consume` to see the messages

Options:
========

- `-t` or `-time` or `--max-runtime` to set a max time (in seconds) before the consumer quits
- `-m` or `-messages` or `--max-messages` to set a max number of messages before the consumer quits

/!\ The consumer will NEVER quit while a message is still processing, it will quit before the next message

Signals:
========
If we send `SIGINT` with `ctrl+c` or `SIGTERM` with `kill`, the consumer will stop gracefully after the current message is done
