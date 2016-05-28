#!/usr/bin/env python
import urllib2
import json
import freeswitch

CNAM_PHP = "http://localhost/app/contact-importer/cnam.php"


def handler(session, args):
    """Do the CID lookup (against our php service...)."""
    response = urllib2.urlopen("%s?call=%s&format=json&debug" % (CNAM_PHP, session.getVariable("uuid")))
    data = json.load(response)
    if "error" in data:
        freeswitch.consoleLog("error", str(data['error']))
    else:
        for key, value in data.iteritems():
            freeswitch.consoleLog("info", str("%s=%s" % (key, value)))
            session.execute("set", str("%s=%s" % (key, value)))
        if "effective_caller_id_name" in data:
            session.execute("set_profile_var", str("caller_id_name=%s" % data['effective_caller_id_name']))
