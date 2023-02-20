import { Client, Transfers } from "appwrite";

const client = new Client();

const transfers = new Transfers(client);

client
    .setEndpoint('https://[HOSTNAME_OR_IP]/v1') // Your API Endpoint
    .setProject('5df5acd0d48c2') // Your project ID
;

const promise = transfers.createAppwriteSource('[PROJECT_ID]', 'https://example.com', '[KEY]');

promise.then(function (response) {
    console.log(response); // Success
}, function (error) {
    console.log(error); // Failure
});