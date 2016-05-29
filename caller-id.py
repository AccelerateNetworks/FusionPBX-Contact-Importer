#!/usr/bin/env python

import sys
import asyncio
import hangups
import json
import code
import protobuf_json

# Path where OAuth refresh token is saved, allowing hangups to remember your
# credentials.
REFRESH_TOKEN_PATH = '/etc/hangouts_token.txt'


def main():
    """Main entry point."""

    # Obtain hangups authentication cookies, prompting for username and
    # password from standard in if necessary.
    cookies = hangups.auth.get_auth_stdin(REFRESH_TOKEN_PATH)

    # Instantiate hangups Client instance.
    client = hangups.Client(cookies)

    # Add an observer to the on_connect event to run the
    # retrieve_suggested_contacts coroutine when hangups has finished
    # connecting.
    client.on_connect.add_observer(lambda: asyncio.async(
        lookup_entities(client, sys.argv[1:])
    ))

    # Start an asyncio event loop by running Client.connect. This will not
    # return until Client.disconnect is called, or hangups becomes
    # disconnected.
    loop = asyncio.get_event_loop()
    loop.run_until_complete(client.connect())


@asyncio.coroutine
def lookup_entities(client, identifiers):
    """Search for entities by phone number, email, or gaia_id."""

    # Instantiate a GetEntityByIdRequest Protocol Buffer message describing the
    # request.

    lookup_dicts = [{'phone': sys.argv[1], 'create_offnetwork_gaia': True}]
    request = hangups.hangouts_pb2.GetEntityByIdRequest(
        request_header=client.get_request_header(),
        batch_lookup_spec=[hangups.hangouts_pb2.EntityLookupSpec(**d)
                           for d in lookup_dicts],
    )

    try:
        # Make the request to the Hangouts API.
        res = yield from client.get_entity_by_id(request)

        # Print the list of entities in the response.
        for entity_result in res.entity_result:
            print(json.dumps(protobuf_json.pb2json(entity_result)))

    finally:
        # Disconnect the hangups Client to make client.connect return.
        yield from client.disconnect()


if __name__ == '__main__':
    main()
