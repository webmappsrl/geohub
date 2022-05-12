# how to backup and restore geohub
## UPDATE .ENV

add [AWS_DUMPS_ACCESS_KEY_ID](https://gist.github.com/peppedeka/2940fef6338e6a1dfef17fcf5b8ee87b), [AWS_DUMPS_SECRET_ACCESS_KEY](https://gist.github.com/peppedeka/2940fef6338e6a1dfef17fcf5b8ee87b) and
 [AWS_DUMPS_BUCKET](https://gist.github.com/peppedeka/2940fef6338e6a1dfef17fcf5b8ee87b) keys
in your local .env, after don't forget to update laravel conf by 
```bash
php artisan config:cache
```

## COMMANDS
### download
```bash
php artisan db:download
```
This command provide to download the `last-dump.sql.gz` from aws bucket, unzip it and store it in `storage/app/database` as `last-dump.sql`
### restore
```bash
php artisan db:restore
```
This command provide to restore the `last-dump.sql` located in `storage/app/database` path.
if you don't have any dump `db:restore` is able to call `db:download` command for download that.

### upload
#### !!! this command deploy in aws bucket use wisely
```bash
php artisan geohub:dump_db
```
This command provide to create a dump of the db, zip it, upload that in aws bucket 
located in [wmdumps/geohub](https://s3.console.aws.amazon.com/s3/buckets/wmdumps?region=eu-central-1&prefix=geohub/&showversions=false)  and update also a last-dump.sql.gz
