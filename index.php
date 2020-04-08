<?php
    // Form a list of all CF IP zones
    // For each zone, grab all A records and TXT records matching $oldip
    // For each matching record, update it to the new IP address

    // Does not deal with paginated zone results so there's currently
    // a maximum of 50 zones managed by this tool

    $authemail = "******";
    $authkey = "*******";

    // Old IP address to find
    $oldip = "42.42.42.42";

    // New IP address to replace the old one with
    $newip = "20.20.20.20";

    $ch = curl_init("https://api.cloudflare.com/client/v4/zones?page=1&per_page=1000&match=all");

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Email: ' . $authemail,
        'X-Auth-Key: ' . $authkey,
        'Content-Type: application/json',
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    $r = json_decode($response, true);
    $result = $r['result'];

    // Predefined list
    $list_domains = array(
        'example-01.com',
        'example-02.com',
        'example-03.com',
        'example-04.com',
    );

    $count = 1;
    foreach ($result as $zone) {
        if (isset($zone['id'])) {
            $zoneid = $zone['id'];
            
            // Check if zone id matches the id
            if (in_array($zoneid, $list_domains)) {
                
                $zonename = $zone['name'];
                echo $count . ": " . $zonename . " " . $zoneid . "<br />\n";
                $count++;

                // List all DNS records for this domain
                $ch = curl_init("https://api.cloudflare.com/client/v4/zones/$zoneid/dns_records");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'X-Auth-Email: ' . $authemail,
                    'X-Auth-Key: ' . $authkey,
                    'Content-Type: application/json',
                ));

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $response = curl_exec($ch);
                curl_close($ch);
                $r = json_decode($response, true);

                $dnsrecs = $r['result'];

                foreach ($dnsrecs as $dns) {
                    if (preg_match("/$oldip/", $dns['content'])) {
                        // OK! Change this DNS record.
                        $newcontent = preg_replace("/$oldip/", $newip, $dns['content']);
                        echo "oldcontent: " . $dns['content'];
                        echo "newcontent: $newcontent<br />\n";

                        // Swap the content then
                        $dns['content'] = $newcontent;
                        updateDNSRecord($dns);
                    }
                }
                echo "<br />\n";

            }

        }
    }

    function updateDNSRecord($dns) {
        global $authemail, $authkey;
        $ch = curl_init("https://api.cloudflare.com/client/v4/zones/" . $dns['zone_id'] . "/dns_records/" . $dns['id']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Auth-Email: ' . $authemail,
            'X-Auth-Key: ' . $authkey,
            'Content-Type: application/json',
        ));

        $data_string = json_encode($dns);
        echo "JSON_DATA_STRING: $data_string";
        echo "<br />\n";

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        $r = json_decode($response, true);

        print_r($r);
        print "<br />\n";
    }
