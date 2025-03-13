            try:
                print("Results before writing to MySQL:", results)  # Debugging line
                write_mysql(results)  # Save results to MySQL
            except Exception as e:
                print("Error writing to MySQL:", e)
