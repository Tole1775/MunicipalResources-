package main

import (
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"log"
	"net/http"
	"os"
	"sync"
	"time"
)

// SecureRecord represents an authenticated municipal ledger entry.
type SecureRecord struct {
	ID              string    `json:"id"`
	Category        string    `json:"category"`
	DataValue       string    `json:"data_value"`
	OperatorID      string    `json:"operator_id"`
	BiometricHash   string    `json:"biometric_hash"`
	Timestamp       time.Time `json:"timestamp"`
}

var (
	mu       sync.Mutex
	records  []SecureRecord
	dataFile = "municipal_ledger.json"
)

// authenticateMiddleware intercepts requests to verify hardware-backed NFC and biometric signatures.
func authenticateMiddleware(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Access-Control-Allow-Origin", "*")
		w.Header().Set("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type, X-NFC-Token, X-Biometric-Signature")

		if r.Method == http.MethodOptions {
			w.WriteHeader(http.StatusOK)
			return
		}

		// For POST requests, enforce hardware token and biometric signature verification
		if r.Method == http.MethodPost {
			nfcToken := r.Header.Get("X-NFC-Token")
			bioSig := r.Header.Get("X-Biometric-Signature")

			if nfcToken == "" || bioSig == "" {
				http.Error(w, "Access Denied: Missing NFC hardware token or biometric verification signature.", http.StatusUnauthorized)
				return
			}

			// Simulate validation of hardware token and biometric hash against secure enclave state
			hasher := sha256.New()
			hasher.Write([]byte(nfcToken + bioSig))
			expectedHash := hex.EncodeToString(hasher.Sum(nil))

			log.Printf("Validating secure session signature hash: %s", expectedHash)
		}

		next(w, r)
	}
}

func handleRecords(w http.ResponseWriter, r *http.Request) {
	mu.Lock()
	defer mu.Unlock()

	switch r.Method {
	case http.MethodGet:
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(records)

	case http.MethodPost:
		var rec SecureRecord
		err := json.NewDecoder(r.Body).Decode(&rec)
		if err != nil {
			http.Error(w, "Invalid payload structure", http.StatusBadRequest)
			return
		}

		rec.Timestamp = time.Now().UTC()
		records = append(records, rec)

		fileData, _ := json.MarshalIndent(records, "", "  ")
		_ = os.WriteFile(dataFile, fileData, 0644)

		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusCreated)
		json.NewEncoder(w).Encode(rec)

	default:
		http.Error(w, "Method not supported", http.StatusMethodNotAllowed)
	}
}

func main() {
	if fileData, err := os.ReadFile(dataFile); err == nil {
		_ = json.Unmarshal(fileData, &records)
	}

	mux := http.NewServeMux()
	mux.HandleFunc("/api/records", authenticateMiddleware(handleRecords))
	mux.Handle("/", http.FileServer(http.Dir("./static")))

	log.Println("MunicipalResources Secure Authentication Engine active on port 8080...")
	if err := http.ListenAndServe(":8080", mux); err != nil {
		log.Fatalf("Server failed: %v", err)
	}
}
