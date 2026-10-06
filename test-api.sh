#!/usr/bin/env bash
# End-to-end API tests against a running server (after `bash setup.sh`):
#   cd ../scholar-app && php artisan serve      # terminal 1
#   bash test-api.sh [http://127.0.0.1:8000]    # terminal 2
# Exits non-zero if anything fails, so CI can use it directly.
B="${1:-http://127.0.0.1:8000}"
OWNER_EMAIL="${CENTRAL_ADMIN_EMAIL:-owner@chenthur.tech}"
OWNER_PASS="${CENTRAL_ADMIN_PASSWORD:-ChangeMe123!}"
TENANT=""; TOKEN=""; CODE=""; BODY=""
p=0; f=0
NL=$'\n'

pass(){ echo "  PASS $1"; p=$((p+1)); }
fail(){ echo "  FAIL $1 -> $(echo "$2" | head -c 240)"; f=$((f+1)); }
# call METHOD PATH [JSON]  — sends X-Tenant/Authorization from $TENANT/$TOKEN
call(){
  local args=(-s -w "${NL}%{http_code}" -X "$1" "$B$2" -H "Accept: application/json" -H "Content-Type: application/json")
  [ -n "$TENANT" ] && args+=(-H "X-Tenant: $TENANT")
  [ -n "$TOKEN" ] && args+=(-H "Authorization: Bearer $TOKEN")
  [ -n "${3:-}" ] && args+=(-d "$3")
  local r; r=$(curl "${args[@]}"); CODE="${r##*$NL}"; BODY="${r%$NL*}"
}
# expect NAME STATUS [GREP-PATTERN]
expect(){
  if [ "$CODE" = "$2" ] && { [ -z "${3:-}" ] || echo "$BODY" | grep -q -- "$3"; }; then pass "$1"; else fail "$1 (HTTP $CODE, wanted $2)" "$BODY"; fi
}
# json DOT.PATH — read a value from the last response body
json(){ echo "$BODY" | php -r '$d=json_decode(stream_get_contents(STDIN),true);foreach(explode(".",$argv[1]) as $k){$d=is_array($d)?($d[$k]??null):null;}echo is_scalar($d)?$d:json_encode($d);' "$1"; }
login(){ TOKEN=""; call POST /api/login "{\"email\":\"$1\",\"password\":\"${2:-password123}\"}"; TOKEN="$(json token)"; }

echo "== HEALTH =="
TENANT=""; TOKEN=""
call GET /api/health; expect "health endpoint" 200 '"status":"ok"'
call GET /api/central/schools; expect "control plane needs auth" 401

echo "== CONTROL PLANE (Chenthur Info Tech owner) =="
call POST /api/central/login "{\"email\":\"$OWNER_EMAIL\",\"password\":\"$OWNER_PASS\"}"; expect "owner login" 200 '"token"'
OWNER="$(json token)"; TOKEN="$OWNER"
call GET /api/central/overview;  expect "overview stats" 200 '"schools_total"'
call GET /api/central/schools;   expect "list schools" 200 'greenfield'
call POST /api/central/licenses '{"licensee":"Test Academy","plan":"pro"}'; expect "issue license" 201 'CHEN-'
KEY="$(json key)"; LIC="$(json id)"
call POST /api/central/licenses '{"licensee":"x","expires_at":"2001-01-01"}'; expect "rejects past expiry" 422
SID_NEW="t$(date +%s)"
call POST /api/central/schools "{\"id\":\"$SID_NEW\",\"name\":\"Test Academy\",\"license_key\":\"$KEY\",\"admin_name\":\"Boss\",\"admin_email\":\"boss@test.test\",\"admin_password\":\"secret123\"}"
expect "provision school" 201 'provisioned'
call POST /api/central/schools "{\"id\":\"${SID_NEW}b\",\"name\":\"Dup\",\"license_key\":\"$KEY\",\"admin_name\":\"B\",\"admin_email\":\"b@t.t\",\"admin_password\":\"secret123\"}"
expect "key cannot be reused" 422 'already assigned'

echo "== SCHOOL IDENTIFICATION =="
TOKEN=""; TENANT=""
call POST /api/login '{"email":"a@b.c","password":"x"}'; expect "missing school id -> 400" 400
TENANT="no-such-school"
call POST /api/login '{"email":"a@b.c","password":"x"}'; expect "unknown school -> 404" 404 'not found'

echo "== SCHOOL ADMIN =="
TENANT="greenfield"
login admin@greenfield.test; [ -n "$TOKEN" ] && pass "school admin login" || { fail "school admin login - server on :8000?" "$BODY"; echo "RESULT: $p passed, $f failed"; exit 1; }
ADMIN="$TOKEN"
call GET /api/dashboard;  expect "dashboard" 200 '"total_students":8'
call GET /api/license;    expect "license status" 200 '"status":"active"'
call GET /api/roles;      expect "roles list" 200 'accountant'
echo "$BODY" | grep -q super-admin && fail "admin cannot grant super-admin" "$BODY" || pass "admin cannot grant super-admin"
call GET /api/me;         ME="$(json id)"
call DELETE "/api/users/$ME"; expect "cannot delete yourself" 422

echo "== STUDENTS =="
call POST /api/students '{"name":"Test Kid","class_name":"Class 6-A","roll_no":99}'; expect "create" 201 'Test Kid'
SID="$(json data.id)"
call PUT "/api/students/$SID" '{"guardian_name":"Parent One"}'; expect "update" 200 'Parent One'
call GET "/api/students?search=Test%20Kid"; expect "search" 200 'Test Kid'
call POST /api/students '{"class_name":"Class 6-A"}'; expect "validation" 422 'name'

echo "== FEES (partial payments + receipt) =="
call POST /api/fees "{\"student_id\":$SID,\"amount\":1000,\"description\":\"Bus fee\"}"; expect "create invoice (auto number)" 201 'INV-'
INV="$(json id)"
call POST "/api/fees/$INV/collect" '{"amount":400,"mode":"upi"}'; expect "partial payment" 200 '"status":"partial"'
call GET "/api/students/$SID"; expect "student fee status synced" 200 '"fee_status":"partial"'
call POST "/api/fees/$INV/collect" '{"amount":5000}'; expect "overpayment rejected" 422
call POST "/api/fees/$INV/collect" '{}'; expect "collect balance" 200 '"status":"paid"'
call GET "/api/fees/$INV/receipt"; expect "receipt" 200 'RCPT-'
call POST "/api/fees/$INV/collect" '{}'; expect "paid invoice cannot be collected" 422

echo "== ATTENDANCE =="
call GET /api/attendance; TODAY="$(json date)"   # the school's own "today" (its timezone), not this machine's
call POST /api/attendance "{\"date\":\"$TODAY\",\"marks\":{\"$SID\":\"present\",\"1\":\"absent\"}}"; expect "save" 200 'saved'
call POST /api/attendance "{\"date\":\"$TODAY\",\"marks\":{\"$SID\":\"leave\"}}"; expect "re-save same day (upsert)" 200
call POST /api/attendance "{\"date\":\"$TODAY\",\"marks\":{\"999999\":\"present\"}}"; expect "unknown student rejected" 422
call GET "/api/attendance?date=$TODAY&class=Class%206-A"; expect "register" 200 '"leave"'

echo "== EXAMS / ADMISSIONS / LIBRARY / TIMETABLE =="
call POST /api/exams "{\"student_id\":$SID,\"exam_name\":\"Unit Test 3\",\"maths\":95,\"science\":92,\"english\":91}"; expect "exam auto-grade" 201 '"grade":"A+"'
call POST /api/admissions '{"applicant_name":"New Kid","class_applied":"Class 6-A"}'; expect "admission" 201 'APP-'
AID="$(json id)"
call POST "/api/admissions/$AID/approve"; expect "approve" 200 'approved'
call POST "/api/admissions/$AID/enroll";  expect "enroll" 201 'enrolled'
ENR="$(json student.id)"
call POST /api/books '{"title":"Test Book","total_copies":1}'; expect "add book" 201
BK="$(json id)"
call POST "/api/books/$BK/issue" '{"borrower":"Test Kid"}'; expect "issue book" 201
ISS="$(json id)"
call POST "/api/books/$BK/issue" '{"borrower":"Other"}'; expect "no copies left" 422
call DELETE "/api/books/$BK"; expect "cannot delete book on loan" 422
call POST "/api/book-issues/$ISS/return"; expect "return book" 200 'returned'
call DELETE "/api/books/$BK"; expect "delete returned book" 204
call GET "/api/timetable?class=Class%2010-A"; expect "timetable grid" 200 'Mathematics'

echo "== REPORTS =="
call GET /api/reports/summary; expect "summary" 200 '"collected"'
call GET /api/reports/export/students; expect "CSV export" 200 'Guardian phone'

echo "== ROLE-WISE ACCESS =="
login teacher@greenfield.test
call GET /api/students;              expect "teacher reads students" 200
call DELETE "/api/students/$SID";    expect "teacher cannot delete student" 403
call GET /api/fees;                  expect "teacher cannot see fees" 403
call GET /api/users;                 expect "teacher cannot manage users" 403
call POST /api/attendance "{\"date\":\"$TODAY\",\"marks\":{\"$SID\":\"present\"}}"; expect "teacher marks attendance" 200
login accountant@greenfield.test
call GET /api/fees;                  expect "accountant sees fees" 200
call POST /api/students '{"name":"X","class_name":"Y"}'; expect "accountant cannot add students" 403
login parent@greenfield.test
call GET /api/announcements;         expect "parent reads announcements" 200
call GET /api/dashboard;             expect "parent has no dashboard" 403
TOKEN="$ADMIN"
call DELETE "/api/students/$SID";    expect "admin deletes student" 204
# leave the demo school as we found it, so the suite can be re-run
call DELETE "/api/students/$ENR" >/dev/null; call DELETE "/api/fees/$INV" >/dev/null; call DELETE "/api/admissions/$AID" >/dev/null

echo "== LICENSE ENFORCEMENT (live, not just at login) =="
TENANT="$SID_NEW"; login boss@test.test secret123; SCHOOL="$TOKEN"
call GET /api/me; expect "new school admin works" 200 'boss@test.test'
TENANT=""; TOKEN="$OWNER"; call POST "/api/central/licenses/$LIC/suspend"; expect "owner suspends key" 200
TENANT="$SID_NEW"; TOKEN="$SCHOOL"; call GET /api/me; expect "existing session blocked" 403 'license_inactive'
login boss@test.test secret123; expect "login blocked" 403
TENANT=""; TOKEN="$OWNER"; call POST "/api/central/licenses/$LIC/activate"; expect "owner re-activates key" 200
TENANT="$SID_NEW"; TOKEN="$SCHOOL"; call GET /api/me; expect "session works again" 200
TENANT=""; TOKEN="$OWNER"; call POST "/api/central/schools/$SID_NEW/suspend"; expect "owner suspends school" 200
call GET "/api/central/licenses/$LIC"; expect "key mirrors school suspension" 200 '"status":"suspended"'
call POST "/api/central/licenses/$LIC/revoke"; expect "revoke key" 200
call POST "/api/central/schools/$SID_NEW/activate"; expect "revoked school cannot be re-activated" 422
call POST "/api/central/licenses/$LIC/activate"; expect "revoked key cannot be re-activated" 422

echo "== SIGN-OUT =="
TENANT="greenfield"; TOKEN=""
call GET /api/dashboard; expect "auth guard" 401
TOKEN="$ADMIN"; call POST /api/logout; expect "logout" 200
call GET /api/dashboard; expect "token revoked" 401

echo "============================="
echo "RESULT: $p passed, $f failed"
[ "$f" = "0" ]
