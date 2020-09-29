Cambridge Zero Widget
===================

Widget to show chart of CO2 in atmosphere; with rolling 7 average and change over 5 years.

Size budget is 200KB hoping to keep it waaaaay under this

## Getting started
Run `npm install` once cloned

`gulp watch` will run a process to watch the source files
`gulp styles` will compile the stylesheet
`gulp script` will compile the JavaScript
`gulp` will compile the JavaScript and stylesheet

Copy `data.json` to `build/`

## Running on a server
Need a cron task set up to execute `server.php` every 6 hours.

`0 */6 * * * php /path/to/server.php >/dev/null 2>&1`

This will read the output file used and compare data if data has been updated the data in file will renew.

### TODO:
- [x] Build tools (Gulp)
- [x] Data feed integration 
- [ ] Implement design including axes labels
- [x] Serve widget (this could be similar to https://wholegrain.gitlab.io/website-carbon-badges/ i.e. use JS to create output)
- [x] Feed updates - request data and update file(s)
