package main

import (
	"encoding/json"
	"log"
	"net/http"
	"os"
	"sync"
	"time"
)

// Record represents a secure municipal data entry payload.
type Record struct {
	ID        string    `json:"id"`
	Category  string    `json:"category"`
	DataValue string    `json:"data_value"`
	Timestamp time.Time `json:"timestamp"`
}

var (
	mu      sync.Mutex
	records []Record
	dataFile = "municipal_ledger.json"
)

// Handle ingestion and data entry requests
func handleRecords(w http.ResponseWriter, r *http.Request) {
	// Enable basic CORS for local interface connectivity
	w.Header().Set("Access-Control-Allow-Origin", "*")
	w.Header().Set("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
	w.Header().Set("Access-Control-Allow-Headers", "Content-Type")

	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusOK)
		return
	}

	mu.Lock()
	defer mu.Unlock()

	switch r.Method {
	case http.MethodGet:
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(records)

	case http.MethodPost:
		var rec Record
		err := json.NewDecoder(r.Body).Decode(&rec)
		if err != nil {
			http.Error(w, "Invalid payload structure", http.StatusBadRequest)
			return
		}

		rec.Timestamp = time.Now().UTC()
		records = append(records, rec)

		// Persist state locally (simulating append-only ledger storage)
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
	// Load existing ledger state if present
	if fileData, err := os.ReadFile(dataFile); err == nil {
		_ = json.Unmarshal(fileData, &records)
	}

	mux := http.NewServeMux()
	mux.HandleFunc("/api/records", handleRecords)

	// Serve the frontend UI directly from root
	mux.Handle("/", http.FileServer(http.Dir("./static")))

	log.Println("Municipal Resources Engine active on port 8080...")
	if err := http.ListenAndServe(":8080", mux); err != nil {
		log.Fatalf("Server failed: %v", err)
	}
}
