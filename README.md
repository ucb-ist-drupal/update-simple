#  DEPRECATED!
This was a one-time workaround for an urgent situation.  The features that this script offers have been incorporated into `istdrupal-updates-apply` and `wps sites:updates` with the options `--minimal`, `--quick`, `--no-rr`.

This code is not maintained.

# Update Simple

This started as a bare-bones bash script `update.sh` which allows applying 
Pantheon upstream updates to a single site.

The script *intentionally neglects to create a backup of the site* or run any 
`clone content` terminus commands, because it is intended for use when the 
Pantheon infrastructure is under stress (Drupalgeddon scenario) and those 
workflows are very slow. 

(We use a much more robust update procedure under normal circumstances.)

```
$ ./update.sh $sitename $last_downstream_environment $distribution_version
```

`update_cohort.php` reads from a cohort.php file (which should contain a `
$sites` array listing sitenames in a cohort.)  It runs multiple site updates in
the background. It accepts a maximum processes argument. It syncs logs to an S3
bucket. If the cohort file name contains "-test" it's assumed that the last 
downstream environment for those sites is the 'test' environment. (We take 
pains not to create the 'live' environment when applying upstream updates.)

```
$ php update_cohort.php /path/to/cohort.php 9.9.9 10
```
