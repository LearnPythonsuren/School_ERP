#!/usr/bin/env bash
# Tests the running API. Start the server first:  php artisan serve
B="${1:-http://127.0.0.1:8000}"
J=(-H "Accept: application/json" -H "Content-Type: application/json")
TH=("${J[@]}" -H "X-Tenant: greenfield")
p=0; f=0
ck(){ echo "$2" | grep -q "$3" && { echo "  PASS $1"; p=$((p+1)); } || { echo "  FAIL $1 -> $(echo "$2"|head -c 120)"; f=$((f+1)); }; }

echo "== CONTROL PLANE (Chenthur Info Tech owner) =="
CT=$(curl -s -X POST "$B/api/central/login" "${J[@]}" -d '{"email":"owner@chenthur.tech","password":"ChangeMe123!"}' | php -r '$d=json_decode(stream_get_contents(STDIN),true);echo $d["token"]??"";')
[ -n "$CT" ] && { echo "  PASS owner login"; p=$((p+1)); } || { echo "  FAIL owner login"; f=$((f+1)); }
CA=(-H "Authorization: Bearer $CT")
ck "list schools"  "$(curl -s "$B/api/central/schools"  "${J[@]}" "${CA[@]}")" 'greenfield'
ck "issue license" "$(curl -s -X POST "$B/api/central/licenses" "${J[@]}" "${CA[@]}" -d '{"licensee":"Demo Co","plan":"pro"}')" 'CHEN-'

echo "== SCHOOL LOGIN + READS =="
TT=$(curl -s -X POST "$B/api/login" "${TH[@]}" -d '{"email":"admin@greenfield.test","password":"password123"}' | php -r '$d=json_decode(stream_get_contents(STDIN),true);echo $d["token"]??"";')
[ -n "$TT" ] && { echo "  PASS school admin login"; p=$((p+1)); } || { echo "  FAIL school admin login - server on :8000?"; f=$((f+1)); exit 1; }
TA=(-H "Authorization: Bearer $TT")
ck "dashboard" "$(curl -s "$B/api/dashboard" "${TH[@]}" "${TA[@]}")" '"total_students":8'
ck "license status" "$(curl -s "$B/api/license" "${TH[@]}" "${TA[@]}")" '"status":"active"'
ck "roles list" "$(curl -s "$B/api/roles" "${TH[@]}" "${TA[@]}")" 'teacher'

echo "== STUDENT CRUD =="
R=$(curl -s -X POST "$B/api/students" "${TH[@]}" "${TA[@]}" -d '{"name":"Test Kid","class_name":"Class 6-A","roll_no":99}'); ck "create" "$R" 'Test Kid'
SID=$(echo "$R" | php -r '$d=json_decode(stream_get_contents(STDIN),true);echo $d["data"]["id"]??($d["id"]??"");')
ck "update" "$(curl -s -X PUT "$B/api/students/$SID" "${TH[@]}" "${TA[@]}" -d '{"fee_status":"paid"}')" 'paid'
ck "delete" "$(curl -s -o /dev/null -w '%{http_code}' -X DELETE "$B/api/students/$SID" "${TH[@]}" "${TA[@]}")" '204'
ck "auth guard" "$(curl -s -o /dev/null -w '%{http_code}' "$B/api/dashboard" "${TH[@]}")" '401'

echo "============================="
echo "RESULT: $p passed, $f failed"
